'use strict';

describe('notesCtrl', function () {
    var scope, configMock, windowMock, $timeout, notesSrvMock, $q, deferred, rootScope;

    beforeEach(angular.mock.module('wallsApp.controllers'));

    beforeEach(inject(function ($rootScope, $controller, _$timeout_, _$q_) {
        rootScope = $rootScope;
        scope = $rootScope.$new();
        scope.note = 'Note 001';
        $q = _$q_;
        configMock = {
            baseurl: 'http://foobar.com/walls', api: 'api/v1',
            instanceid: 8,
            pollInterval: 10000,
            messages: {
                confirmDeleteNote: 'foo'
            }
        };
        windowMock = {
            location: {
                href: ''
            },
            confirm: function () {}
        };
        $timeout = _$timeout_;
        deferred = [];
        deferred.getNotes = $q.defer();
        deferred.postNote = $q.defer();
        deferred.putNote = $q.defer();
        deferred.deleteNote = $q.defer();
        notesSrvMock = {
            getNotes: function () {
                return deferred.getNotes.promise;
            },
            postNote: function () {
                return deferred.postNote.promise;
            },
            putNote: function () {
                return deferred.putNote.promise;
            },
            deleteNote: function () {
                return deferred.deleteNote.promise;
            }
        };
        $controller('notesCtrl', {
            $scope: scope,
            $window: windowMock,
            $timeout: $timeout,
            notesSrv: notesSrvMock,
            CONFIG: configMock
        });
    }));

    describe('general behaviour', function () {
        it('should cancel updating when \'startDragging\' is broadcasted', function () {
            scope.$digest();
            spyOn($timeout, 'cancel');
            rootScope.$broadcast('startDragging');
            expect($timeout.cancel).toHaveBeenCalled();
        });
    });

    describe('method getNotes', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.getNotes)).toBe(true);
        });

        it('should get a list of notes', function () {
            scope.$digest();
            deferred.getNotes.resolve({
                notes: [{
                    instanceid: 1,
                    wallid: 1,
                    userfullname: 'Admin User',
                    note: 'Note 001'
                }, {
                    instanceid: 1,
                    wallid: 1,
                    userfullname: 'Admin User',
                    note: 'Note 002'
                }],
                total: 2
            });
            scope.$digest();
            expect(scope.notes.length).toEqual(2);
            expect(scope.notes.length).toEqual(scope.total);
        });

        it('should call itself again after a timeout', function () {
            scope.$digest();
            spyOn(scope, 'getNotes');
            scope.getNotes();
            deferred.getNotes.resolve({
                notes: [],
                total: 0
            });
            expect(scope.getNotes.calls.count()).toEqual(1);
            scope.$digest();
            $timeout.flush(9999);
            expect(scope.getNotes.calls.count()).toEqual(1);
            $timeout.flush(1);
            expect(scope.getNotes.calls.count()).toEqual(2);
        });
    });

    describe('method postNote', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.postNote)).toBe(true);
        });

        it('should call notesSrv postNote', function () {
            scope.$digest();
            spyOn(scope, 'postNote').and.callThrough();
            scope.postNote({
                note: 'Note 001',
                xcoord: 50,
                ycoord: 50
            });
            deferred.postNote.resolve({
                notes: [],
                total: 0
            });
            scope.$digest();
            expect(scope.editingId).toBeNull();
            expect(scope.postNote).toHaveBeenCalled();
        });
    });

    describe('method putNote', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.putNote)).toBe(true);
        });

        it('should call notesSrv putNote', function () {
            scope.$digest();
            spyOn(scope, 'putNote').and.callThrough();
            scope.putNote({
                note: 'Note 001',
                xcoord: 50,
                ycoord: 50
            });
            deferred.putNote.resolve({
                notes: [],
                total: 0
            });
            scope.$digest();
            expect(scope.editingId).toBeNull();
            expect(scope.putNote).toHaveBeenCalled();
        });
    });

    describe('method deleteNote', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.deleteNote)).toBe(true);
        });

        it('should call notesSrv deleteNote', function () {
            scope.$digest();
            spyOn(scope, 'deleteNote').and.callThrough();
            scope.deleteNote(1);
            deferred.deleteNote.resolve({
                notes: [],
                total: 0
            });
            scope.$digest();
            expect(scope.deleteNote).toHaveBeenCalled();
        });

        it('should popup a confirm box', function () {
            scope.$digest();
            spyOn(windowMock, 'confirm').and.returnValue(true);
            scope.deleteNote(1);
            expect(windowMock.confirm).toHaveBeenCalled();
        });

        it('should set editingId to a value when startEditing() is called', function () {
            scope.$digest();
            expect(scope.editingId).toBe(null);
            scope.startEditing(15);
            expect(scope.editingId).toEqual(15);
        });

        it('should set editingId to null when stopEditing() is called', function () {
            scope.$digest();
            scope.editingId = 1;
            expect(scope.editingId).toEqual(1);
            scope.stopEditing();
            $timeout.flush(10000);
            expect(scope.editingId).toBe(null);
        });
    });

    describe('method backToCommunityWalls', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.backToCommunityWalls)).toBe(true);
        });

        it('should navigate to the community walls page (the activity page)', function () {
            scope.backToCommunityWalls();
            expect(windowMock.location.href).toEqual(configMock.baseurl + '/' + configMock.instanceid);
        });
    });
});
