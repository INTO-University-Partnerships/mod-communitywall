'use strict';

var app = angular.module('wallsApp.controllers', []);

app.controller('wallsCtrl', [
    '$scope', '$timeout', '$window', 'wallsSrv', 'CONFIG',
    function ($scope, $timeout, $window, wallsSrv, config) {
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
            wallsSrv.getPageOfWalls(currentPage, $scope.perPage).
                then(function (data) {
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
            wallsSrv.deleteWall(wallid).
                then(function () {
                    // if deleting a wall results in one less page of walls, move the current page back one
                    if ($scope.currentPage > 0 && (($scope.total - 1) % $scope.perPage) === 0) {
                        --$scope.currentPage;
                    }
                    $scope.getPageOfWalls($scope.currentPage);
                });
        };
    }
]);

app.controller('notesCtrl', [
    '$scope', '$timeout', '$window', 'notesSrv', 'CONFIG',
    function ($scope, $timeout, $window, notesSrv, config) {
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
            notesSrv.getNotes().
                then(function (data) {
                    $scope.notes = data.notes;
                    $scope.total = data.total;
                    $scope.timeoutPromise = $timeout($scope.getNotes, 10000);
                });
        };

        $scope.postNote = function (note) {
            $scope.note = '';
            $scope.addingNote = false;
            notesSrv.postNote(note).
                then(function () {
                    $scope.getNotes();
                });
        };

        $scope.putNote = function (noteid, note) {
            $scope.editingId = null;
            notesSrv.putNote(noteid, note).finally(function () {
                $scope.getNotes();
            });
        };

        $scope.deleteNote = function (noteid) {
            if (!$window.confirm('Are you sure you want to delete this note?')) {
                return;
            }
            notesSrv.deleteNote(noteid).finally(function () {
                $scope.getNotes();
            });
        };

        $scope.backToCommunityWalls = function () {
            $window.location.href = config.baseurl + '/' + config.instanceid;
        };

        $scope.getNotes();
    }
]);
