'use strict';

var app = angular.module('wallsApp.directives', []);

var KEY_CODE_ESCAPE = 27;

/**
 * randomize coordinates relative to the top-left of the community wall note container
 * @returns {object}
 */
var getRandomCoords = function () {
    var o = {x: 0, y: 0};
    var containerElems = document.getElementsByClassName('communitywall-note-container');
    if (containerElems && containerElems.length) {
        var containerDOMRect = containerElems[0].getBoundingClientRect();
        var addNoteElems = document.getElementsByClassName('add-note');
        if (addNoteElems && addNoteElems.length) {
            var addNoteDOMRect = addNoteElems[0].getBoundingClientRect();
            o.x = Math.floor((containerDOMRect.width - addNoteDOMRect.width) * Math.random());
            o.y = Math.floor((containerDOMRect.height - addNoteDOMRect.height) * Math.random());
        }
    }
    return o;
};

app.directive('wallListItem', ['CONFIG',
    function (config) {
        return {
            restrict: 'E',
            scope: {
                wall: '=',
                baseurl: '@',
                canManage: '@',
                editT: '&',
                deleteT: '&'
            },
            templateUrl: config.baseurl + '/partials/wallListItem.twig'
        };
    }
]);

app.directive('communitywallNoteContainer', [
    function () {
        return {
            restrict: 'A',
            link: function (scope, element) {
                element.bind('click', function ($event) {
                    scope.$broadcast('containerClicked', $event);
                });
            }
        };
    }
]);

app.directive('addNote', ['CONFIG', '$timeout',
    function (config, $timeout) {
        return {
            restrict: 'E',
            scope: {
                saveChanges: '&',
                stopAdding: '&',
                addingNote: '=',
                isGuest: '='
            },
            link: function (scope, element) {
                // appear/disappear with opacity
                scope.disappear = function () {
                    element.find('div').parent().css('opacity', 0.0);
                };
                scope.appear = function () {
                    element.find('div').parent().css('opacity', 1.0);
                };
                scope.disappear();

                // display the 'addNote' element in a random position
                scope.$watch('addingNote', function (newValue, oldValue) {
                    if (!(newValue === true && oldValue === false)) {
                        return;
                    }

                    // immediately set actual note text to the empty string
                    scope.noteText = '';

                    // wait an instant before setting coordinates (otherwise bounding rect has area of 0)
                    $timeout(function () {
                        // randomize coordinates
                        var o = getRandomCoords();
                        scope.xcoord = o.x;
                        scope.ycoord = o.y;

                        // Set the actual element to the clicked position
                        element.css({
                            top: scope.ycoord + 'px',
                            left: scope.xcoord + 'px'
                        });

                        // set focus to text area
                        element.find('textarea')[0].focus();
                        scope.appear();
                    }, 1);
                });

                // save and hide
                scope.$on('containerClicked', function () {
                    if (scope.addingNote === false) {
                        return;
                    }

                    // save note if not empty
                    if (!!scope.noteText.length) {
                        scope.saveChanges()({
                            note: scope.noteText,
                            xcoord: scope.xcoord,
                            ycoord: scope.ycoord
                        });
                    }

                    scope.stopAdding()(true);
                    scope.disappear();
                });

                // key press
                scope.onKeyPress = function (event) {
                    if (event.keyCode === KEY_CODE_ESCAPE) {
                        scope.stopAdding()(false);
                        scope.disappear();
                    }
                };
            },
            templateUrl: config.baseurl + '/partials/addNote.twig'
        };
    }
]);

app.directive('viewNote', ['CONFIG', '$timeout', '$rootScope', 'notesSrv',
    function (config, $timeout, $rootScope, notesSrv) {
        return {
            restrict: 'E',
            scope: {
                canManage: '=',
                dragging: '=',
                editingId: '=',
                startEditing: '&',
                stopEditing: '&',
                saveChanges: '&',
                deleteNote: '&',
                note: '=',
                ngTitle: '='
            },
            link: function (scope, element) {
                element.attr('title', scope.ngTitle);
                scope.temporarilyIgnoreClicks = false;

                // focus handler
                var textarea = element.find('textarea');
                textarea.focus(function () {
                    var l = scope.note.note.length;
                    this.setSelectionRange(l, l);
                    return false;
                }).mouseup(function () {
                    return false;
                });

                // initial position
                element.css({
                    top: scope.note.ycoord + 'px',
                    left: scope.note.xcoord + 'px',
                    zIndex: scope.note.id
                });

                // watch editingId
                scope.$watch('editingId', function (newValue, oldValue) {
                    // save changes if a new note has been clicked
                    if (!!newValue && !!oldValue && newValue !== oldValue && oldValue === scope.note.id) {
                        notesSrv.putNote(scope.note.id, {
                            note: scope.note.note
                        });
                        return;
                    }

                    // set focus to textarea
                    if (!!newValue && newValue === scope.note.id) {
                        scope.editingDisabled = true;
                        notesSrv.getNoteText(scope.note.id).then(function (data) {
                            scope.editingDisabled = false;
                            scope.initialNoteText = scope.note.note = data;
                            $timeout(function () {
                                textarea.focus();
                            }, 1);
                        });
                    }
                });

                // save changes when user clicks the container (only when user is actually editing)
                scope.$on('containerClicked', function () {
                    if (scope.editingId === scope.note.id) {
                        scope.saveChanges()({
                            id: scope.note.id,
                            note: scope.note.note
                        });
                    }
                });

                // click
                element.bind('click', function ($event) {
                    $event.stopPropagation();

                    if (scope.temporarilyIgnoreClicks) {
                        return;
                    }

                    if (scope.editingId === scope.note.id) {
                        return;
                    }

                    // if the user is also the owner or admin
                    if (scope.note.is_owner || scope.canManage) {
                        scope.startEditing()(scope.note.id);
                    }
                });

                // start dragging
                element.bind('dragstart', function ($event) {
                    // prevent dragging if any note is currently in edit mode
                    if (scope.editingId !== null) {
                        $event.preventDefault();
                        return;
                    }

                    $rootScope.$broadcast('startDragging');
                });

                // drop dragging
                element.bind('dragstop', function () {
                    // after dragging, save new location
                    scope.saveChanges()({
                        id: scope.note.id,
                        xcoord: angular.element(element).position().left || 0,
                        ycoord: angular.element(element).position().top || 0
                    });

                    // temporarily ignore clicks immediately after dragging
                    scope.temporarilyIgnoreClicks = true;
                    $timeout(function () {
                        scope.temporarilyIgnoreClicks = false;
                    }, 100);
                });

                // key press
                scope.onKeyPress = function (event) {
                    if (event.keyCode === KEY_CODE_ESCAPE) {
                        scope.note.note = scope.initialNoteText;
                        scope.stopEditing()();
                    }
                };
            },
            templateUrl: config.baseurl + '/partials/viewNote.twig'
        };
    }
]);
