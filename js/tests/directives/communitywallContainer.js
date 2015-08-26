'use strict';

describe('addNote', function () {
    var config, element, scope, $timeout;

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

    beforeEach(inject(function (_$timeout_) {
        $timeout = _$timeout_;
    }));

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

        it('should broadcast $event after two clicks', function () {
            scope.$digest();
            spyOn(scope, '$broadcast');
            expect(scope.clicks).toBe(0);
            element.click();
            expect(scope.clicks).toBe(1);
            element.click();
            expect(scope.$broadcast).toHaveBeenCalled();
        });

        it('should set the variable clicks to 0 after x milliseconds', function () {
            scope.$digest();
            element.click();
            expect(scope.clicks).toBe(1);
            $timeout.flush(300);
            expect(scope.clicks).toBe(0);
        });

        it('should broadcast $event on single click', function () {
            scope.$digest();
            spyOn(scope, '$broadcast');
            element.click();
            expect(scope.$broadcast).toHaveBeenCalled();
        });

        it('should trigger addClass when mouseover element after x seconds', function () {
            scope.$digest();
            $timeout.flush(2000);
            expect(scope.slideUp).toBe(false);
            scope.$digest();
            element.trigger('mouseover');
            expect(scope.slideUp).toBe(true);
        });
    });
});
