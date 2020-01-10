var overwriteWithout = function(arr, item) {
    for (var i = arr.length; i >= 0; i--) {
        if (arr[i] === item) {
            arr.splice(i, 1);
        }
    }
};

var isSet = function(scope, expr) {
    if (_.isUndefined(expr)) {
        return false;
    }
    if (expr === '') {
        return true;
    }
    return scope.$eval(expr);
};


app.factory('shortcuts', [
    '$document',
    function($document) {
        var shortcuts = [];

        var charKeyCodes = {
            'delete': 8,
            'tab': 9,
            'enter': 13,
            'return': 13,
            'esc': 27,
            'space': 32,
            'left': 37,
            'up': 38,
            'right': 39,
            'down': 40,
            ';': 186,
            '=': 187,
            ',': 188,
            '-': 189,
            '.': 190,
            '/': 191,
            '`': 192,
            '[': 219,
            '\\': 220,
            ']': 221,
            "'": 222,
        };

        var inOrder = function(keys, initial) {
            var len = keys.length;
            for (var i = 0; i < len; i++) {
                charKeyCodes[keys[i]] = initial + i;
            }
        };

        inOrder('1234567890', 49);
        inOrder('abcdefghijklmnopqrstuvwxyz', 65);

        var keyCodeChars = {};
        _.forEach(charKeyCodes, function(keyCode, character) {
            keyCodeChars[keyCode] = character;
        });

        var modifierKeys = {
            'shift': 'shift',
            'ctrl': 'ctrl',
            'meta': 'meta',
            'alt': 'alt'
        };

        var parseKeySet = function(keySet) {
            var names = keySet.split('+');
            var keys = {};

            // Default modifiers to unset.
            _.forEach(modifierKeys, function(name) {
                keys[name] = false;
            });

            _.forEach(names, function(name) {
                var modifierKey = modifierKeys[name];
                if (modifierKey) {
                    keys[modifierKey] = true;
                } else {
                    keys.keyCode = charKeyCodes[name];

                    // In case someone tries for a weird key.
                    if (!keys.keyCode) {
                        return;
                    }
                }
            });

            return keys;
        };

        var parseEvent = function(e) {
            var keys = {};
            keys.keyCode = charKeyCodes[keyCodeChars[e.which]];
            keys.meta = e.metaKey || false;
            keys.alt = e.altKey || false;
            keys.ctrl = e.ctrlKey || false;
            keys.shift = e.shiftKey || false;
            return keys;
        }

        var match = function(k1, k2) {
            return (
                k1.keyCode === k2.keyCode &&
                k1.ctrl === k2.ctrl &&
                k1.alt === k2.alt &&
                k1.meta === k2.meta &&
                k1.shift === k2.shift
            );
        };

        $document.bind('keydown', function(e) {
            // Don't catch keys that were in inputs.
            var $target = $(e.target);
            if ($target.is('input[type="text"], textarea')) {
                return;
            }

            var eventKeys = parseEvent(e);
            var shortcut;
            for (var i = shortcuts.length - 1; i >= 0; i--) {
                shortcut = shortcuts[i];
                if (match(eventKeys, shortcut.keys)) {
                    e.preventDefault();

                    // NOTE: the action is responsible for $scope.$apply!
                    shortcut.action();
                    return;
                }
            }
        });

        return {
            shortcuts: shortcuts,
            register: function(shortcut) {
                shortcut.keys = parseKeySet(shortcut.keySet);

                // Be lenient.
                if (!shortcut.keys) {
                    return;
                }

                shortcuts.push(shortcut);
                return shortcut;
            },
            unregister: function(shortcut) {
                overwriteWithout(shortcuts, shortcut);
            }
        };
    }
]);

app.directive('ngShortcut', [
    '$parse',
    'shortcuts',
    function($parse, shortcuts) {
        return {
            restrict: 'A',
            link: function(scope, element, attrs) {
                var shortcutKeySets = scope.$eval(attrs.ngShortcut);
                if (_.isUndefined(shortcutKeySets)) {
                    return;
                }
                shortcutKeySets = shortcutKeySets.split('|');

                var action = _.ignore;
                var eventAction = function(event) {
                    return function() {
                        element.trigger(event);
                    };
                };
                if (isSet(scope, attrs.ngShortcutClick)) {
                    action = eventAction('click');
                } else if (isSet(scope, attrs.ngShortcutFocus)) {
                    action = eventAction('focus');
                } else if (isSet(scope, attrs.ngShortcutFastClick)) {
                    // Since we are just triggering (not binding)
                    // this works just fine.
                    action = eventAction('click');
                } else if (attrs.ngShortcutNavigate) {
                    var url = scope.$eval(attrs.ngShortcutNavigate);
                    action = function() {
                        navigation.redirect(url, true);
                    };
                } else if (attrs.ngShortcutAction) {
                    var fn = $parse(attrs.ngShortcutAction);
                    action = function() {
                        scope.$apply(function() {
                            fn(scope);
                        });
                    };
                }

                _.forEach(shortcutKeySets, function(keySet) {
                    var shortcut = shortcuts.register({
                        keySet: keySet,
                        action: action,
                        description: attrs.ngShortcutDescription || ''
                    });
                    scope.$on("$destroy", function() {
                        shortcuts.unregister(shortcut);
                    });
                });
            }
        }
    }
]);