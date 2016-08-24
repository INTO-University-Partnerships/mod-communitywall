(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

var app = angular.module('wallsApp', ['wallsApp.controllers', 'wallsApp.services', 'wallsApp.directives', 'ngSanitize', 'ngDragDrop']);

app.constant('CONFIG', window.CONFIG);
delete window.CONFIG;

},{}],2:[function(require,module,exports){
'use strict';

require('./angular-app');

require('./controllers');

require('./directives');

require('./services');

},{"./angular-app":1,"./controllers":3,"./directives":4,"./services":5}],3:[function(require,module,exports){
'use strict';

var app = angular.module('wallsApp.controllers', []);

app.controller('wallsCtrl', ['$scope', '$timeout', '$window', 'wallsSrv', 'CONFIG', function ($scope, $timeout, $window, wallsSrv, config) {
    $scope.walls = [];
    $scope.total = -1;
    $scope.perPage = 5;
    $scope.currentPage = 0;
    $scope.baseurl = config.baseurl;
    $scope.canManage = config.canManage;
    $scope.closed = config.closed;
    $scope.isGuest = config.isGuest;
    $scope.timeoutPromise = null;

    $scope.prevPage = function () {
        if ($scope.currentPage > 0) {
            --$scope.currentPage;
        }
    };

    $scope.prevPageDisabled = function () {
        return $scope.currentPage === 0 ? 'disabled' : '';
    };

    $scope.nextPage = function () {
        if ($scope.currentPage < $scope.pageCount() - 1) {
            $scope.currentPage++;
        }
    };

    $scope.nextPageDisabled = function () {
        return $scope.currentPage === $scope.pageCount() - 1 ? 'disabled' : '';
    };

    $scope.pageCount = function () {
        if ($scope.total === 0) {
            return 1;
        }
        return Math.ceil($scope.total / $scope.perPage);
    };

    $scope.getPageOfWalls = function (currentPage) {
        $timeout.cancel($scope.timeoutPromise);
        wallsSrv.getPageOfWalls(currentPage, $scope.perPage).then(function (data) {
            $scope.walls = data.walls;
            $scope.total = data.total;
            $scope.timeoutPromise = $timeout(function () {
                $scope.getPageOfWalls($scope.currentPage);
            }, config.pollInterval);
        });
    };

    $scope.$watch('currentPage', function (newValue) {
        $scope.getPageOfWalls(newValue);
    });

    $scope.addWall = function () {
        $window.location.href = config.baseurl + '/' + config.instanceid + '/add';
    };

    $scope.editWall = function (wallid) {
        $window.location.href = config.baseurl + '/' + config.instanceid + '/edit/' + wallid;
    };

    $scope.deleteWall = function (wallid) {
        if (!$window.confirm(config.messages.confirmDeleteWall)) {
            return;
        }
        wallsSrv.deleteWall(wallid).then(function () {
            // if deleting a wall results in one less page of walls, move the current page back one
            if ($scope.currentPage > 0 && ($scope.total - 1) % $scope.perPage === 0) {
                --$scope.currentPage;
            }
            $scope.getPageOfWalls($scope.currentPage);
        });
    };
}]);

app.controller('notesCtrl', ['$scope', '$timeout', '$window', 'notesSrv', 'CONFIG', function ($scope, $timeout, $window, notesSrv, config) {
    $scope.baseurl = config.baseurl;
    $scope.canManage = config.canManage;
    $scope.closed = config.closed;
    $scope.isGuest = config.isGuest;
    $scope.notes = null;
    $scope.total = null;
    $scope.timeoutPromise = null;
    $scope.editingId = null;
    $scope.addingNote = false;
    $scope.noteContainerDisabled = false;

    var stopTimeout = function stopTimeout() {
        $timeout.cancel($scope.timeoutPromise);
    };

    var startTimeout = function startTimeout() {
        $timeout.cancel($scope.timeoutPromise);
        $scope.timeoutPromise = $timeout($scope.getNotes, config.pollInterval);
    };

    $scope.startEditing = function (noteId) {
        $scope.editingId = noteId;
        stopTimeout();
        $scope.$digest();
    };

    $scope.stopAdding = function (digest, event) {
        if (typeof event !== 'undefined') {
            event.stopPropagation();
        }
        $scope.addingNote = false;
        if (digest) {
            $scope.$digest();
        }
    };

    $scope.stopEditing = function (event) {
        if (typeof event !== 'undefined') {
            event.stopPropagation();
        }
        $scope.editingId = null;
        startTimeout();
    };

    $scope.$on('startDragging', function () {
        stopTimeout();
    });

    $scope.setNotes = function (data) {
        $scope.notes = data.notes;
        $scope.total = data.total;
        startTimeout();
        $scope.noteContainerDisabled = false;
    };

    $scope.getNotes = function () {
        stopTimeout();
        $scope.noteContainerDisabled = true;
        notesSrv.getNotes().then($scope.setNotes);
    };

    $scope.postNote = function (note, event) {
        if (typeof event !== 'undefined') {
            event.stopPropagation();
        }
        stopTimeout();
        $scope.addingNote = false;
        $scope.noteContainerDisabled = true;
        notesSrv.postNote({
            note: note.note,
            xcoord: note.xcoord,
            ycoord: note.ycoord
        }).then($scope.setNotes);
    };

    $scope.putNote = function (note, event) {
        if (typeof event !== 'undefined') {
            event.stopPropagation();
        }
        stopTimeout();
        $scope.editingId = null;
        $scope.noteContainerDisabled = true;
        notesSrv.putNote(note.id, note).then($scope.setNotes);
    };

    $scope.deleteNote = function (noteId, event) {
        if (typeof event !== 'undefined') {
            event.stopPropagation();
        }
        if (!$window.confirm(config.messages.confirmDeleteNote)) {
            return;
        }
        stopTimeout();
        $scope.editingId = null;
        $scope.noteContainerDisabled = true;
        notesSrv.deleteNote(noteId).then($scope.setNotes);
    };

    $scope.backToCommunityWalls = function () {
        $window.location.href = config.baseurl + '/' + config.instanceid;
    };

    $scope.getNotes();
}]);

},{}],4:[function(require,module,exports){
'use strict';

var app = angular.module('wallsApp.directives', []);

var KEY_CODE_ESCAPE = 27;

/**
 * randomize coordinates relative to the top-left of the community wall note container
 * @returns {object}
 */
var getRandomCoords = function getRandomCoords() {
    var o = { x: 0, y: 0 };
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

app.directive('wallListItem', ['CONFIG', function (config) {
    return {
        restrict: 'E',
        scope: {
            wall: '=',
            baseurl: '@',
            canManage: '@',
            closed: '@',
            editT: '&',
            deleteT: '&'
        },
        templateUrl: config.baseurl + '/partials/wallListItem.twig'
    };
}]);

app.directive('communitywallNoteContainer', [function () {
    return {
        restrict: 'A',
        link: function link(scope, element) {
            element.bind('click', function ($event) {
                scope.$broadcast('containerClicked', $event);
            });
        }
    };
}]);

app.directive('addNote', ['CONFIG', '$timeout', function (config, $timeout) {
    return {
        restrict: 'E',
        scope: {
            saveChanges: '&',
            stopAdding: '&',
            addingNote: '=',
            isGuest: '='
        },
        link: function link(scope, element) {
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
}]);

app.directive('viewNote', ['CONFIG', '$timeout', '$rootScope', 'notesSrv', function (config, $timeout, $rootScope, notesSrv) {
    return {
        restrict: 'E',
        scope: {
            canManage: '=',
            closed: '=',
            editingId: '=',
            startEditing: '&',
            stopEditing: '&',
            saveChanges: '&',
            deleteNote: '&',
            note: '=',
            ngTitle: '='
        },
        link: function link(scope, element) {
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
                if (scope.note.is_owner && !scope.closed || scope.canManage) {
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
}]);

},{}],5:[function(require,module,exports){
'use strict';

var app = angular.module('wallsApp.services', []);

app.service('wallsSrv', ['$http', '$q', 'CONFIG', function ($http, $q, config) {
    var url = config.baseurl + config.api + '/wall/' + config.instanceid;

    this.getPageOfWalls = function (page, perPage) {
        var deferred = $q.defer();
        $http.get(url + '?limitfrom=' + page * perPage + '&limitnum=' + perPage).success(function (data) {
            deferred.resolve(data);
        }).error(function (data) {
            deferred.reject(data);
        });
        return deferred.promise;
    };

    this.deleteWall = function (wallid) {
        var deferred = $q.defer();

        $http.delete(url + '/' + wallid).success(function (data) {
            deferred.resolve(data);
        }).error(function (data) {
            deferred.reject(data);
        });
        return deferred.promise;
    };
}]);

app.service('notesSrv', ['$http', '$q', 'CONFIG', function ($http, $q, config) {
    var url = config.baseurl + config.api + '/wall/' + config.instanceid + '/' + config.wallid + '/note';

    this.getNotes = function () {
        var deferred = $q.defer();
        $http.get(url).success(function (data) {
            deferred.resolve(data);
        }).error(function (data) {
            deferred.reject(data);
        });
        return deferred.promise;
    };

    this.getNoteText = function (noteid) {
        var deferred = $q.defer();
        $http.get(url + '/text/' + noteid).success(function (data) {
            deferred.resolve(data);
        }).error(function (data) {
            deferred.reject(data);
        });
        return deferred.promise;
    };

    this.postNote = function (note) {
        var deferred = $q.defer();
        $http.post(url, note).success(function (data) {
            deferred.resolve(data);
        }).error(function (data) {
            deferred.reject(data);
        });
        return deferred.promise;
    };

    this.putNote = function (noteid, note) {
        var deferred = $q.defer();
        $http.put(url + '/' + noteid, note).success(function (data) {
            deferred.resolve(data);
        }).error(function (data) {
            deferred.reject(data);
        });
        return deferred.promise;
    };

    this.deleteNote = function (noteid) {
        var deferred = $q.defer();
        $http.delete(url + '/' + noteid).success(function (data) {
            deferred.resolve(data);
        }).error(function (data) {
            deferred.reject(data);
        });
        return deferred.promise;
    };
}]);

},{}]},{},[2]);
