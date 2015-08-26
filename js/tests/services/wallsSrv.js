'use strict';

describe('wallsSrv', function () {
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

    beforeEach(inject(function (wallsSrv, _$httpBackend_) {
        srv = wallsSrv;
        $httpBackend = _$httpBackend_;
    }));

    afterEach(function () {
        $httpBackend.verifyNoOutstandingExpectation();
        $httpBackend.verifyNoOutstandingRequest();
    });

    describe('method getPageOfWalls', function () {
        it('should exist', function () {
            expect(angular.isFunction(srv.getPageOfWalls)).toBe(true);
        });

        it('should fetch data from the server', function () {
            $httpBackend.expectGET('http://foobar.com/walls/api/v1/wall/8?limitfrom=0&limitnum=5').
                respond({
                    walls: [
                        {
                            id: 1,
                            some_property: "some_value_1"
                        },
                        {
                            id: 2,
                            some_property: "some_value_2"
                        }
                    ],
                    total: 2
                });
            var promise = srv.getPageOfWalls(0, 5);
            $httpBackend.flush();
            promise.then(function (data) {
                expect(data.walls).toEqual([
                    {
                        id: 1,
                        some_property: "some_value_1"
                    },
                    {
                        id: 2,
                        some_property: "some_value_2"
                    }
                ]);
                expect(data.total).toEqual(2);
            });
        });

        it('should handle errors', function () {
            $httpBackend.expectGET('http://foobar.com/walls/api/v1/wall/8?limitfrom=0&limitnum=5').
                respond(500, 'something bad happened');
            var promise = srv.getPageOfWalls(0, 5);
            var spy = jasmine.createSpy();
            promise.then(function () {
                // empty
            }, spy);
            expect(spy).not.toHaveBeenCalled();
            $httpBackend.flush();
            expect(spy).toHaveBeenCalledWith('something bad happened');
        });
    });

    describe('method deleteWall', function () {
        it('should exist', function () {
            expect(angular.isFunction(srv.deleteWall)).toBe(true);
        });

        it('should delete data from the server', function () {
            $httpBackend.expectDELETE('http://foobar.com/walls/api/v1/wall/8/17').
                respond(204);
            srv.deleteWall(17);
            $httpBackend.flush();
        });

        it('should handle errors', function () {
            $httpBackend.expectDELETE('http://foobar.com/walls/api/v1/wall/8/17').
                respond(500, 'something bad happened');
            var promise = srv.deleteWall(17);
            var spy = jasmine.createSpy();
            promise.then(function () {
                // empty
            }, spy);
            expect(spy).not.toHaveBeenCalled();
            $httpBackend.flush();
            expect(spy).toHaveBeenCalledWith('something bad happened');
        });
    });
});
