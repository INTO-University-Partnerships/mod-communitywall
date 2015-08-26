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
            baseurl: 'http://foobar.com/walls',
            api: 'api/v1',
            instanceid: 8
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

        it('should call itself again after a 10s timeout', function () {
            scope.$digest();
            spyOn(scope, 'getNotes');
            scope.getNotes();
            deferred.getNotes.resolve({
                notes: [],
                total: 0
            });
            expect(scope.getNotes.calls.length).toEqual(1);
            scope.$digest();
            $timeout.flush(9999);
            expect(scope.getNotes.calls.length).toEqual(1);
            $timeout.flush(1);
            expect(scope.getNotes.calls.length).toEqual(2);
        });
    });

    describe('method postNote', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.postNote)).toBe(true);
        });

        it('should call getNotes', function () {
            scope.$digest();
            spyOn(scope, 'getNotes').andCallThrough();
            scope.postNote('Note 001');
            deferred.postNote.resolve();
            scope.$digest();
            expect(scope.getNotes).toHaveBeenCalled();
            expect(scope.addingNote).toBe(false);
        });
    });

    describe('method putNote', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.putNote)).toBe(true);
        });

        it('should call notesSrv putNote', function () {
            scope.$digest();
            spyOn(scope, 'putNote').andCallThrough();
            scope.putNote(1, 'Note 001');
            deferred.putNote.resolve();
            scope.$digest();
            expect(scope.editingId).toBeNull();
            expect(scope.putNote).toHaveBeenCalled();
        });

        it('should call getNotes', function () {
            scope.$digest();
            spyOn(scope, 'getNotes').andCallThrough();
            scope.putNote(1, 'Note 001');
            deferred.putNote.resolve();
            scope.$digest();
            expect(scope.getNotes).toHaveBeenCalled();
        });

        it('should call getNotes when a reject is send back from the services', function () {
            scope.$digest();
            spyOn(scope, 'getNotes').andCallThrough();
            scope.putNote(1, 'Note 001');
            deferred.putNote.reject();
            scope.$digest();
            expect(scope.getNotes).toHaveBeenCalled();
        });
    });

    describe('method deleteNote', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.deleteNote)).toBe(true);
        });

        it('should call notesSrv deleteNote', function () {
            scope.$digest();
            spyOn(scope, 'deleteNote').andCallThrough();
            scope.deleteNote(1);
            deferred.deleteNote.resolve();
            scope.$digest();
            expect(scope.deleteNote).toHaveBeenCalled();
        });

        it('should popup a confirm box', function () {
            scope.$digest();
            spyOn(windowMock, 'confirm').andReturn(true);
            scope.deleteNote(1);
            expect(windowMock.confirm).toHaveBeenCalled();
        });

        it('should call getNotes', function () {
            scope.$digest();
            spyOn(scope, 'getNotes').andCallThrough();
            spyOn(windowMock, 'confirm').andReturn(true);
            scope.deleteNote(1);
            deferred.deleteNote.resolve();
            scope.$digest();
            expect(scope.getNotes).toHaveBeenCalled();
        });

        it('should set editingId to a value when startEditing is called', function () {
            scope.$digest();
            expect(scope.editingId).toBe(null);
            scope.startEditing({id: 1});
            expect(scope.editingId).toEqual(1);
        });

        it('should set editingId to null and call getnotes', function () {
            scope.$digest();
            scope.editingId = 1;
            spyOn(scope, 'getNotes').andCallThrough();
            expect(scope.editingId).toEqual(1);
            scope.stopEditing();
            $timeout.flush(10000);
            expect(scope.editingId).toBe(null);
            expect(scope.getNotes).toHaveBeenCalled();
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
