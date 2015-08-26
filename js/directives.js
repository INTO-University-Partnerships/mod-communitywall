'use strict';

var app = angular.module('wallsApp.directives', []);

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

app.directive('communitywallNoteContainer', ['CONFIG', '$rootScope', '$timeout',
    function (config, $rootScope, $timeout) {
        return {
            restrict: 'A',
            link: function (scope, element) {
                scope.clicks = 0;
                scope.timer = null;

                element.bind('click', function ($event) {
                    scope.clicks++;
                    if (scope.clicks === 1) {
                        scope.timer = $timeout(function () {
                            scope.clicks = 0;
                        }, 300);
                    }

                    // if clicks === 0, that means there is a doubleclick which should invoke the doubleclick event
                    if (scope.clicks === 2) {
                        scope.addingNote = true;
                        scope.$broadcast('sendDblClickEvent', $event);
                        clearTimeout(scope.timer);
                        scope.clicks = 0;
                    } else {
                        scope.$broadcast('sendClickEvent', $event);
                    }
                    scope.$digest();
                });

                scope.slideUp = false;
                $timeout(function () {
                    element.bind('mouseover', function () {
                        scope.slideUp = true;
                        scope.$digest();
                    });
                }, 2000);
            }
        };
    }
]);

app.directive('addNote', ['CONFIG',
    function (config) {
        return {
            restrict: 'E',
            scope: {
                saveChanges: '&',
                note: '=',
                addingNote: '='
            },
            link: function (scope, element) {
                // Set focus to textarea
                scope.$watch(function () { return element.is(':visible'); }, function () {
                    scope.note = '';
                    element.find('textarea')[0].focus();
                });

                // Receive doubleClickEvent which should display the 'addNote' element on the clicked position
                scope.$on('sendDblClickEvent', function (s, $event) {
                    scope.xcoord = $event.originalEvent.pageX;
                    scope.ycoord = $event.originalEvent.pageY;
                    // Not all browsers support pageX and pageY
                    if (typeof scope.xcoord === 'undefined' && typeof scope.ycoord === 'undefined') {
                        scope.xcoord = $event.clientX;
                        scope.ycoord = $event.clientY;
                    }
                    // Element should be positioned relative to container + substract 12px (10px padding and 2px border)
                    scope.xcoord -= element.closest('.communitywall-note-container').offset().left + 12;
                    scope.ycoord -= element.closest('.communitywall-note-container').offset().top + 12;
                    // Set the actual element to the clicked position
                    element.css({
                        top: scope.ycoord + 'px',
                        left: scope.xcoord + 'px'
                    });
                });

                // Receive clickEvent,
                // Save and hide
                scope.$on('sendClickEvent', function () {
                    if (scope.addingNote === false) {
                        return;
                    }
                    // (saving note if not empty)
                    if (scope.note.length !== 0) {
                        scope.saveChanges({
                            note: scope.note,
                            xcoord: scope.xcoord,
                            ycoord: scope.ycoord
                        });
                    }
                    scope.addingNote = false;
                    scope.$digest();
                });
            },
            templateUrl: config.baseurl + '/partials/addNote.twig'
        };
    }
]);

app.directive('viewNote', ['CONFIG', '$timeout', '$rootScope',
    function (config, $timeout, $rootScope) {
        return {
            restrict: 'E',
            scope: {
                canManage: '=',
                dragging: '=',
                isEditing: '=',
                startEditing: '&',
                stopEditing: '&',
                saveChanges: '&',
                deleteNote: '&',
                note: '=',
                ngTitle: '='

            },
            link: function (scope, element) {
                scope.clicks = 0;
                scope.timer = null;

                scope.tempNote = angular.copy(scope.note);

                element.attr('title', scope.ngTitle);

                // Initial position
                element.css({
                    top: scope.note.ycoord + 'px',
                    left: scope.note.xcoord + 'px',
                    zIndex: scope.note.id
                });

                // Set focus to textarea
                scope.$watch('isEditing', function(newValue) {
                    if (!newValue) {
                        return;
                    }
                    $timeout(function () {
                        // Get textarea length to set caret to end of the field
                        var textarea = element.find('textarea');
                        var l = textarea.val().length;
                        textarea[0].focus();
                        textarea[0].setSelectionRange(l, l);
                    }, 1);
                });

                // Stop editing when user clicks outside the element (only when user is actually editing)
                scope.$on('sendClickEvent', function () {
                    if (scope.isEditing === true) {
                        if (angular.equals(scope.tempNote, scope.note)) {
                            return scope.stopEditing();
                        }
                        scope.saveChanges({
                            id: scope.note.id,
                            note: scope.note.note,
                            xcoord: scope.note.xcoord,
                            ycoord: scope.note.ycoord
                        });
                    }
                });

                element.bind('click', function($event) {
                    scope.clicks++;
                    if (scope.clicks === 1) {
                        scope.timer = $timeout(function () {
                            scope.clicks = 0;
                        }, 300);
                    }

                    // if clicks === 2, it is a doubleclick so invoke startEditing,
                    // if the user is also the owner or admin
                    if (scope.clicks === 2 && (scope.note.is_owner || scope.canManage)) {
                        clearTimeout(scope.timer);
                        scope.clicks = 0;
                        scope.startEditing();
                        scope.$digest();
                    }

                    $event.stopPropagation();
                });

                // While dragging timeout should be stopped so no updates are triggered
                element.bind('dragstart', function ($event) {
                    // Prevent dragging if the note is currently in edit mode
                    if (scope.isEditing === true) {
                        return $event.preventDefault();
                    }
                    $rootScope.$broadcast('startDragging');
                }).bind('dragstop', function () {
                    // When stop dragging, the new location should be saved
                    scope.note.xcoord = angular.element(element).position().left || 0;
                    scope.note.ycoord = angular.element(element).position().top || 0;
                    scope.saveChanges({
                        id: scope.note.id,
                        note: scope.note.note,
                        xcoord: scope.note.xcoord,
                        ycoord: scope.note.ycoord
                    });
                });

            },
            templateUrl: config.baseurl + '/partials/viewNote.twig'
        };
    }
]);
