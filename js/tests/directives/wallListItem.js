'use strict';

describe('wallListItem', function () {
    var config, element, scope;

    beforeEach(function () {
        config = {
            baseurl: ''
        };
    });

    beforeEach(angular.mock.module('wallsApp.directives', ['$provide',
        function ($provide) {
            $provide.value('CONFIG', config);
        }
    ]));

    beforeEach(inject(function ($templateCache) {
        // put the directive's template in the template cache
        var tmpl =
            '<div>' +
                '<span><a href="{{ baseurl }}/wall/{{ wall.id }}">{{ wall.title }}</a></span>' +
                '<span>by&nbsp;{{ wall.userfullname }}</span>' +
                '<span class="right" ng-show="wall.is_owner || canManage">' +
                    '<button ng-click="editT({wallid: wall.id})">Edit</button>' +
                    '<button ng-click="deleteT({wallid: wall.id})">Delete</button>' +
                '</span>' +
            '</div>';
        $templateCache.put(config.baseurl + '/partials/wallListItem.twig', tmpl);
    }));

    describe('general behaviours', function () {
        beforeEach(inject(function ($rootScope, $compile) {
            // create scope
            scope = $rootScope.$new();
            scope.wall = {
                title: 'Some amazing wall',
                userfullname: 'Tyrion Lannister',
                is_owner: true
            };
            scope.baseurl = 'http://foobar.com';
            scope.canManage = false;

            // compile html
            var html = '<wall-list-item wall="wall" baseurl="{{ baseurl }}" can-manage="{{ canManage }}" edit-t="editWall(wall.id)" delete-t="deleteWall(wall.id)"></wall-list-item>';
            element = $compile(angular.element(html))(scope);
        }));

        it('should render the title of the wall', function () {
            scope.$digest();
            expect(element.html()).toContain(scope.wall.title);
        });

        /*it('should render the full username of the author of the wall', function () {
            scope.$digest();
            expect(element.html()).toContain(scope.wall.userfullname);
        });*/
    });
    /*
    describe('edit and delete buttons', function () {
        beforeEach(inject(function ($rootScope, $compile) {
            // create scope
            scope = $rootScope.$new();
            scope.wall = {
                id: 17,
                is_owner: true
            };
            scope.canManage = false;
            scope.editWall = jasmine.createSpy();
            scope.deleteWall = jasmine.createSpy();

            // compile html
            var html = '<wall-list-item wall="wall" baseurl="{{ baseurl }}" can-manage="{{ canManage }}" edit-t="editWall(wall.id)" delete-t="deleteWall(wall.id)"></wall-list-item>';
            element = $compile(angular.element(html))(scope);
        }));

        it("(the edit button) should invoked the 'editWall' method when clicked", function () {
            scope.$digest();
            element.find('span.right :first-child').click();
            expect(scope.editWall).toHaveBeenCalledWith(17);
            expect(scope.deleteWall).not.toHaveBeenCalled();
        });

        it("(the delete button) should invoked the 'deleteWall' method when clicked", function () {
            scope.$digest();
            element.find('span.right :last-child').click();
            expect(scope.deleteWall).toHaveBeenCalledWith(17);
            expect(scope.editWall).not.toHaveBeenCalled();
        });
    });

    describe('when cannot manage and owner of wall', function () {
        beforeEach(inject(function ($rootScope, $compile) {
            // create scope
            scope = $rootScope.$new();
            scope.wall = {
                is_owner: true
            };
            scope.canManage = false;

            // compile html
            var html = '<wall-list-item wall="wall" baseurl="{{ baseurl }}" can-manage="{{ canManage }}" edit-t="editWall(wall.id)" delete-t="deleteWall(wall.id)"></wall-list-item>';
            element = $compile(angular.element(html))(scope);
        }));

        it('should render edit and delete buttons', function () {
            scope.$digest();
            expect(element.find('span.right').hasClass('ng-hide')).toBeFalsy();
        });
    });

    describe('when cannot manage and not owner of wall', function () {
        beforeEach(inject(function ($rootScope, $compile) {
            // create scope
            scope = $rootScope.$new();
            scope.wall = {
                is_owner: false
            };
            scope.canManage = false;

            // compile html
            var html = '<wall-list-item wall="wall" baseurl="{{ baseurl }}" can-manage="{{ canManage }}" edit-t="editWall(wall.id)" delete-t="deleteWall(wall.id)"></wall-list-item>';
            element = $compile(angular.element(html))(scope);
        }));

        it('should not render edit and delete buttons', function () {
            scope.$digest();
            expect(element.find('span.right').hasClass('ng-hide')).toBeTruthy();
        });
    });

    describe('when can manage and not owner of wall', function () {
        beforeEach(inject(function ($rootScope, $compile) {
            // create scope
            scope = $rootScope.$new();
            scope.wall = {
                is_owner: false
            };
            scope.canManage = true;

            // compile html
            var html = '<wall-list-item wall="wall" baseurl="{{ baseurl }}" can-manage="{{ canManage }}" edit-t="editWall(wall.id)" delete-t="deleteWall(wall.id)"></wall-list-item>';
            element = $compile(angular.element(html))(scope);
        }));

        it('should render edit and delete buttons', function () {
            scope.$digest();
            expect(element.find('span.right').hasClass('ng-hide')).toBeFalsy();
        });
    });*/
});
