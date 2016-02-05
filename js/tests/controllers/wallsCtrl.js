'use strict';

describe('wallsCtrl', function () {
    var scope, configMock, windowMock, $timeout, wallsSrvMock, $q, deferred;

    beforeEach(angular.mock.module('wallsApp.controllers'));

    beforeEach(inject(function ($rootScope, $controller, _$timeout_, _$q_) {
        scope = $rootScope.$new();
        $q = _$q_;
        configMock = {
            baseurl: 'http://foobar.com/walls',
            api: 'api/v1',
            instanceid: 8,
            canManage: true,
            pollInterval: 10000,
            messages: {
                confirmDeleteWall: 'foo'
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
        deferred.deleteWall = $q.defer();
        deferred.getPageOfWalls = $q.defer();
        wallsSrvMock = {
            deleteWall: function () {
                return deferred.deleteWall.promise;
            },
            getPageOfWalls: function () {
                return deferred.getPageOfWalls.promise;
            }
        };
        $controller('wallsCtrl', {
            $scope: scope,
            $window: windowMock,
            $timeout: $timeout,
            wallsSrv: wallsSrvMock,
            CONFIG: configMock
        });
    }));

    describe('method addWall', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.addWall)).toBe(true);
        });

        it('should navigate to the page that lets you add a new wall', function () {
            scope.addWall();
            expect(windowMock.location.href).toEqual(configMock.baseurl + '/' + configMock.instanceid + '/add');
        });
    });

    describe('method editWall', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.editWall)).toBe(true);
        });

        it('should navigate to the page that lets you edit an existing wall', function () {
            scope.editWall(13);
            expect(windowMock.location.href).toEqual(configMock.baseurl + '/' + configMock.instanceid + '/edit/13');
        });
    });

    describe('method deleteWall', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.deleteWall)).toBe(true);
        });

        it('should provide a confirmation dialog', function () {
            spyOn(windowMock, 'confirm');
            scope.deleteWall(13);
            expect(windowMock.confirm.calls.count()).toEqual(1);
        });

        it('should delegate to the wallsSrv service', function () {
            spyOn(windowMock, 'confirm').and.returnValue(true);
            spyOn(wallsSrvMock, 'deleteWall').and.returnValue({
                then: function () {}
            });
            scope.deleteWall(13);
            expect(wallsSrvMock.deleteWall).toHaveBeenCalledWith(13);
            expect(wallsSrvMock.deleteWall.calls.count()).toEqual(1);
        });

        it('should get page of walls when resolved', function () {
            spyOn(windowMock, 'confirm').and.returnValue(true);
            spyOn(scope, 'getPageOfWalls');
            scope.deleteWall(17);
            deferred.deleteWall.resolve();
            expect(scope.getPageOfWalls).not.toHaveBeenCalled();
            scope.$digest();
            expect(scope.getPageOfWalls).toHaveBeenCalledWith(scope.currentPage);
        });

        it('should move the current page back one if the last wall on the current page was deleted', function () {
            spyOn(windowMock, 'confirm').and.returnValue(true);
            scope.perPage = 5;
            scope.total = 6;
            scope.currentPage = 1;
            scope.deleteWall(6);
            deferred.deleteWall.resolve();
            scope.$digest();
            expect(scope.currentPage).toEqual(0);
        });
    });

    describe('method prevPage', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.prevPage)).toBe(true);
        });

        it('should move to the previous page', function () {
            scope.currentPage = 1;
            scope.prevPage();
            expect(scope.currentPage).toEqual(0);
        });

        it('should do nothing if on the first page', function () {
            scope.currentPage = 0;
            scope.prevPage();
            expect(scope.currentPage).toEqual(0);
        });
    });

    describe('method prevPageDisabled', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.prevPageDisabled)).toBe(true);
        });

        it('should return "disabled" if on the first page', function () {
            scope.currentPage = 0;
            expect(scope.prevPageDisabled()).toEqual('disabled');
        });

        it('should return "" (i.e. an empty string) if not on the first page', function () {
            var i;
            for (i = 1; i < 10; ++i) {
                scope.currentPage = i;
                expect(scope.prevPageDisabled()).toEqual('');
            }
        });
    });

    describe('method nextPage', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.nextPage)).toBe(true);
        });

        it('should move to the next page', function () {
            scope.currentPage = 8;
            spyOn(scope, 'pageCount').and.returnValue(10);
            scope.nextPage();
            expect(scope.currentPage).toEqual(9);
        });

        it('should do nothing if on the last page', function () {
            scope.currentPage = 9;
            spyOn(scope, 'pageCount').and.returnValue(10);
            scope.nextPage();
            expect(scope.currentPage).toEqual(9);
        });
    });

    describe('method nextPageDisabled', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.nextPageDisabled)).toBe(true);
        });

        it('should return "disabled" if on the last page', function () {
            scope.currentPage = 9;
            spyOn(scope, 'pageCount').and.returnValue(10);
            expect(scope.nextPageDisabled()).toEqual('disabled');
        });

        it('should return "" (i.e. an empty string) if not on the last page', function () {
            var i;
            spyOn(scope, 'pageCount').and.returnValue(10);
            for (i = 0; i < 9; ++i) {
                scope.currentPage = i;
                expect(scope.nextPageDisabled()).toEqual('');
            }
        });
    });

    describe('method pageCount', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.pageCount)).toBe(true);
        });

        it('should calculate the number of pages correctly', function () {
            expect(scope.perPage).toEqual(5);
            scope.total = 24;
            expect(scope.pageCount()).toEqual(5);
            scope.total = 25;
            expect(scope.pageCount()).toEqual(5);
            scope.total = 26;
            expect(scope.pageCount()).toEqual(6);
            scope.total = 5;
            expect(scope.pageCount()).toEqual(1);
            scope.total = 4;
            expect(scope.pageCount()).toEqual(1);

            // we want to tell the user there's one page, even if there's no items on it
            scope.total = 0;
            expect(scope.pageCount()).toEqual(1);
        });
    });

    describe('method getPageOfWalls', function () {
        it('should exist', function () {
            expect(angular.isFunction(scope.getPageOfWalls)).toBe(true);
        });

        it('should delegate to the wallsSrv service', function () {
            var i;
            spyOn(wallsSrvMock, 'getPageOfWalls').and.returnValue({
                then: function () {}
            });
            for (i = 0; i < 5; ++i) {
                scope.getPageOfWalls(i);
                expect(wallsSrvMock.getPageOfWalls).toHaveBeenCalledWith(i, scope.perPage);
            }
            expect(wallsSrvMock.getPageOfWalls.calls.count()).toEqual(5);
        });

        it("should cancel any pending promise that hasn't been resolved", function () {
            spyOn($timeout, 'cancel');
            spyOn(wallsSrvMock, 'getPageOfWalls').and.returnValue({
                then: function () {}
            });
            scope.timeoutPromise = $timeout(function () {}, 1000);
            scope.getPageOfWalls(0);
            expect($timeout.cancel).toHaveBeenCalledWith(scope.timeoutPromise);
        });

        it('should set some scope variables when resolved', function () {
            scope.getPageOfWalls(3, 5);
            deferred.getPageOfWalls.resolve({
                walls: [
                    {
                        id: 3
                    },
                    {
                        id: 5
                    },
                    {
                        id: 8
                    }
                ],
                total: 3
            });
            scope.$digest();
            expect(scope.walls).toEqual([
                {
                    id: 3
                },
                {
                    id: 5
                },
                {
                    id: 8
                }
            ]);
            expect(scope.total).toEqual(3);
        });

        it('should call itself again after a 10s timeout', function () {
            scope.$digest();
            spyOn(scope, 'getPageOfWalls').and.callThrough();
            scope.getPageOfWalls(0);
            deferred.getPageOfWalls.resolve({
                walls: [],
                total: 0
            });
            expect(scope.getPageOfWalls.calls.count()).toEqual(1);
            scope.$digest();
            $timeout.flush(9999);
            expect(scope.getPageOfWalls.calls.count()).toEqual(1);
            $timeout.flush(1);
            expect(scope.getPageOfWalls.calls.count()).toEqual(2);
        });
    });

    describe('currentPage watch', function () {
        it('should invoke getPageOfWalls when the controller is initialized', function () {
            spyOn(scope, 'getPageOfWalls');
            scope.$digest();
            expect(scope.getPageOfWalls).toHaveBeenCalledWith(0);
        });

        it('should invoke getPageOfWalls with newValue when changed', function () {
            spyOn(scope, 'getPageOfWalls');
            scope.$digest();

            scope.currentPage = 1;
            scope.$digest();
            expect(scope.getPageOfWalls).toHaveBeenCalledWith(1);

            scope.currentPage = 2;
            scope.$digest();
            expect(scope.getPageOfWalls).toHaveBeenCalledWith(2);

            expect(scope.getPageOfWalls.calls.count()).toEqual(3);
        });
    });
});
