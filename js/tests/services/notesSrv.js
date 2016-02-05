'use strict';

describe('notesSrv', function () {
    var srv, $httpBackend, configMock;

    beforeEach(angular.mock.module('wallsApp.services', function ($provide) {
        configMock = {
            baseurl: 'http://foobar.com/walls',
            api: '/api/v1',
            instanceid: 8,
            wallid: 17
        };
        $provide.value('CONFIG', configMock);
    }));

    beforeEach(inject(function (notesSrv, _$httpBackend_) {
        srv = notesSrv;
        $httpBackend = _$httpBackend_;
    }));

    afterEach(function () {
        $httpBackend.verifyNoOutstandingExpectation();
        $httpBackend.verifyNoOutstandingRequest();
    });

    describe('method getNotes', function () {
        it('should exist', function () {
            expect(angular.isFunction(srv.getNotes)).toBe(true);
        });

        it('should receive notes', function () {
            $httpBackend.expectGET('http://foobar.com/walls/api/v1/wall/8/17/note').
                respond({
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
            var promise = srv.getNotes();
            $httpBackend.flush();
            promise.then(function (data) {
                expect(data.notes).toEqual([
                    {
                        instanceid: 1,
                        wallid: 1,
                        userfullname: 'Admin User',
                        note: 'Note 001'
                    }, {
                        instanceid: 1,
                        wallid: 1,
                        userfullname: 'Admin User',
                        note: 'Note 002'
                    }
                ]);
                expect(data.total).toEqual(2);
            });
        });

        it('should handle errors', function () {
            $httpBackend.expectGET('http://foobar.com/walls/api/v1/wall/8/17/note').
                respond(500, 'not ok!');
            var promise = srv.getNotes();
            var spy = jasmine.createSpy();
            promise.then(function () {
                // Empty
            }, spy);
            expect(spy).not.toHaveBeenCalled();
            $httpBackend.flush();
            expect(spy).toHaveBeenCalledWith('not ok!');
        });
    });

    describe('method postNote', function () {
        it('should exist', function () {
            expect(angular.isFunction(srv.postNote)).toBe(true);
        });

        it('should POST data to the server', function () {
            var data = {
                note: 'This is a test note.'
            };
            $httpBackend.expectPOST('http://foobar.com/walls/api/v1/wall/8/17/note', data).
                respond(204);
            var promise = srv.postNote(data);
            var spy = jasmine.createSpy();
            promise.then(spy);
            expect(spy).not.toHaveBeenCalled();
            $httpBackend.flush();
            expect(spy).toHaveBeenCalled();
        });

        it('should handle errors', function () {
            $httpBackend.expectPOST('http://foobar.com/walls/api/v1/wall/8/17/note').
                respond(500, 'not ok!');
            var promise = srv.postNote('');
            var spy = jasmine.createSpy();
            promise.then(function () {
                // Empty
            }, spy);
            expect(spy).not.toHaveBeenCalled();
            $httpBackend.flush();
            expect(spy).toHaveBeenCalledWith('not ok!');
        });
    });

    describe('method putNote', function () {
        it('should exist', function () {
            expect(angular.isFunction(srv.putNote)).toBe(true);
        });

        it('should PUT data to the server', function () {
            var data = {
                id: 1,
                note: 'This is a test note.'
            };
            $httpBackend.expectPUT('http://foobar.com/walls/api/v1/wall/8/17/note/1', data).
                respond(204);
            var promise = srv.putNote(1, data);
            var spy = jasmine.createSpy();
            promise.then(spy);
            expect(spy).not.toHaveBeenCalled();
            $httpBackend.flush();
            expect(spy).toHaveBeenCalled();
        });

        it('should handle errors', function () {
            var data = {
                id: 99,
                note: 'This is a test note.'
            };
            $httpBackend.expectPUT('http://foobar.com/walls/api/v1/wall/8/17/note/99').
                respond(500, 'not ok!');
            var promise = srv.putNote(99, data);
            var spy = jasmine.createSpy();
            promise.then(function () {
                // Empty
            }, spy);
            expect(spy).not.toHaveBeenCalled();
            $httpBackend.flush();
            expect(spy).toHaveBeenCalledWith('not ok!');
        });
    });

    describe('method deleteNote', function () {
        it('should exist', function () {
            expect(angular.isFunction(srv.deleteNote)).toBe(true);
        });

        it('should send a DELETE request to the server', function () {
            $httpBackend.expectDELETE('http://foobar.com/walls/api/v1/wall/8/17/note/1').
                respond(204);
            var promise = srv.deleteNote(1);
            var spy = jasmine.createSpy();
            promise.then(spy);
            expect(spy).not.toHaveBeenCalled();
            $httpBackend.flush();
            expect(spy).toHaveBeenCalled();
        });

        it('should handle errors', function () {
            $httpBackend.expectDELETE('http://foobar.com/walls/api/v1/wall/8/17/note/99').
                respond(500, 'not ok!');
            var promise = srv.deleteNote(99);
            var spy = jasmine.createSpy();
            promise.then(function () {
                // Empty
            }, spy);
            expect(spy).not.toHaveBeenCalled();
            $httpBackend.flush();
            expect(spy).toHaveBeenCalledWith('not ok!');
        });
    });
});
