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
            }, 10000);
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
        if (!$window.confirm('Are you sure?')) {
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
    $scope.notes = null;
    $scope.total = null;
    $scope.baseurl = config.baseurl;
    $scope.canManage = config.canManage;
    $scope.isGuest = config.isGuest;
    $scope.timeoutPromise = null;
    $scope.editingId = null;
    $scope.addingNote = false;

    $scope.startEditing = function (note) {
        $scope.editingId = note.id;
        // Stop the updating of notes
        $timeout.cancel($scope.timeoutPromise);
    };

    $scope.stopEditing = function () {
        $scope.editingId = null;
        $scope.timeoutPromise = $timeout($scope.getNotes, 10000);
    };

    $scope.$on('startDragging', function () {
        $timeout.cancel($scope.timeoutPromise);
    });

    $scope.getNotes = function () {
        $timeout.cancel($scope.timeoutPromise);
        notesSrv.getNotes().then(function (data) {
            $scope.notes = data.notes;
            $scope.total = data.total;
            $scope.timeoutPromise = $timeout($scope.getNotes, 10000);
        });
    };

    $scope.postNote = function (note) {
        $scope.note = '';
        $scope.addingNote = false;
        notesSrv.postNote(note).then(function () {
            $scope.getNotes();
        });
    };

    $scope.putNote = function (noteid, note) {
        $scope.editingId = null;
        notesSrv.putNote(noteid, note)['finally'](function () {
            $scope.getNotes();
        });
    };

    $scope.deleteNote = function (noteid) {
        if (!$window.confirm('Are you sure you want to delete this note?')) {
            return;
        }
        notesSrv.deleteNote(noteid)['finally'](function () {
            $scope.getNotes();
        });
    };

    $scope.backToCommunityWalls = function () {
        $window.location.href = config.baseurl + '/' + config.instanceid;
    };

    $scope.getNotes();
}]);

},{}],4:[function(require,module,exports){
'use strict';

var app = angular.module('wallsApp.directives', []);

app.directive('wallListItem', ['CONFIG', function (config) {
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
}]);

app.directive('communitywallNoteContainer', ['CONFIG', '$rootScope', '$timeout', function (config, $rootScope, $timeout) {
    return {
        restrict: 'A',
        link: function link(scope, element) {
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
}]);

app.directive('addNote', ['CONFIG', function (config) {
    return {
        restrict: 'E',
        scope: {
            saveChanges: '&',
            note: '=',
            addingNote: '='
        },
        link: function link(scope, element) {
            // Set focus to textarea
            scope.$watch(function () {
                return element.is(':visible');
            }, function () {
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
}]);

app.directive('viewNote', ['CONFIG', '$timeout', '$rootScope', function (config, $timeout, $rootScope) {
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
        link: function link(scope, element) {
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
            scope.$watch('isEditing', function (newValue) {
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

            element.bind('click', function ($event) {
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

        $http['delete'](url + '/' + wallid).success(function (data) {
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
        $http['delete'](url + '/' + noteid).success(function (data) {
            deferred.resolve(data);
        }).error(function (data) {
            deferred.reject(data);
        });
        return deferred.promise;
    };
}]);

},{}]},{},[2]);
