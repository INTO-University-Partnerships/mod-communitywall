'use strict';

describe('addNote', function () {
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

    describe('general behaviour', function () {
        beforeEach(inject(function ($rootScope, $compile) {
            scope = $rootScope.$new();
            scope.note = 'Note 001';
            // compile html
            var html =
                '<div class="communitywall-note-container" communitywall-note-container ng-cloak>' +
                    '<div class="notice">' +
                        '<span>Double click anywhere to post a note.</span>' +
                    '</div>' +
                '</div>';
            element = $compile(angular.element(html))(scope);
        }));

        it('should broadcast $event on click', function () {
            scope.$digest();
            spyOn(scope, '$broadcast');
            element.click();
            expect(scope.$broadcast).toHaveBeenCalled();
        });
    });
});
