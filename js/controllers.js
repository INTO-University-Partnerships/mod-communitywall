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

        var stopTimeout = function () {
            $timeout.cancel($scope.timeoutPromise);
        };

        var startTimeout = function () {
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
    }
]);
