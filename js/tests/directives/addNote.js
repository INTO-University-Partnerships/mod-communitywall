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

    beforeEach(inject(function ($templateCache) {
        // put the directive's template in the template cache
        var tmpl =
                '<div ng-click="$event.stopPropagation()">' +
                '   <textarea ng-model="note"></textarea>' +
                '   <div class="clearfix"></div>' +
                '   <button ng-disabled="note.length == 0" ng-click="saveChanges({ note:note, xcoord: xcoord, ycoord: ycoord })">savechanges</button>' +
                '   <button ng-click="addingNote = false">cancel</button>' +
                '</div>';
        $templateCache.put(config.baseurl + '/partials/addNote.twig', tmpl);
    }));

    describe('general behaviour', function () {
        beforeEach(inject(function ($rootScope, $compile) {
            // create scope
            scope = $rootScope.$new();
            scope.postNote = jasmine.createSpy();
            scope.addingNote = false;
            scope.note = 'Note 001';
            scope.saveChanges = jasmine.createSpy();

            // compile html
            var html =
                    '<div class="communitywall-note-container" communitywall-note-container><add-note ng-cloak' +
                    '          note="note"' +
                    '          adding-note="addingNote"' +
                    '          save-changes="postNote({note: note, xcoord: xcoord, ycoord: ycoord})"' +
                    '          ng-show="addingNote"' +
                    '          class="add-note"></add-note></div>';
            element = $compile(angular.element(html))(scope);
        }));

        it('should be hidden on init', function () {
            scope.$digest();
            expect(element.is(':visible')).toBe(false);
        });

        it('should render a text textarea', function () {
            scope.$digest();
            expect(element.html()).toMatch(new RegExp(/<textarea.*ng-model="note"/));
        });

        it('should render two buttons', function () {
            scope.$digest();
            expect(element.html()).toMatch(new RegExp(/<button.*ng-click="saveChanges(.*)".*>savechanges/));
            expect(element.html()).toMatch(new RegExp(/<button.*ng-click="addingNote = false".*>cancel/));
        });

        it('should receive coordinates via broadcast sendDblClickEvent', function () {
            scope.$digest();
            var e = $.Event('dblClick');
            e.originalEvent = e;
            spyOn(scope, '$broadcast').andCallThrough();
            scope.$broadcast('sendDblClickEvent', e);
            expect(scope.$broadcast).toHaveBeenCalled();
        });

        it('should set x and ycoord if pageX and pageY don\'t exist', function () {
            scope.$digest();
            var e = $.Event('dblClick');
            e.originalEvent = {
                pageX: 0,
                pageY: 0
            };
            scope.$broadcast('sendDblClickEvent', e);
            expect(scope.xcoord).not.toBe(null);
            expect(scope.ycoord).not.toBe(null);
            expect(element.css('left')).not.toBe(null);
            expect(element.css('top')).not.toBe(null);
        });

        it('should receive a sendClickEvent', function () {
            scope.$digest();
            scope.note = 'Note 001';
            spyOn(scope, '$broadcast').andCallThrough();
            var e = $.Event('click');
            e.originalEvent = e;
            scope.$broadcast('sendClickEvent', e);
            expect(scope.$broadcast).toHaveBeenCalled();
        });

        it('should return nothing when addingNote = false', function () {
            scope.$digest();
            scope.addingNote = false;
            scope.$digest();
            spyOn(scope, '$broadcast').andCallThrough();
            var e = $.Event('click');
            scope.$broadcast('sendClickEvent', e);
            expect(scope.$broadcast).toHaveBeenCalled();
        });

        it('should save changes when required variables are set', function () {
            scope.$digest();
            scope.addingNote = true;
            scope.note = 'Note 003';
            scope.$digest();
            var e = $.Event('click');
            scope.$broadcast('sendClickEvent', e);
            scope.$digest();
            expect(scope.postNote).toHaveBeenCalled();
        });

        it('should not save changes when not all required variables are set', function () {
            scope.$digest();
            scope.addingNote = true;
            scope.note = '';
            scope.$digest();
            var e = $.Event('click');
            scope.$broadcast('sendClickEvent', e);
            scope.$digest();
            expect(scope.postNote).not.toHaveBeenCalled();
        });
    });

    describe('buttons', function () {
        beforeEach(inject(function ($rootScope, $compile) {
            // create scope
            scope = $rootScope.$new();
            scope.postNote = jasmine.createSpy();
            scope.addingNote = false;
            scope.note = 'Note 001';

            // compile html¶
            var html =
                     '<div class="communitywall-note-container" communitywall-note-container><add-note ng-cloak' +
                     '          note="note"' +
                     '          adding-note="addingNote"' +
                     '          save-changes="postNote({note: note, xcoord: xcoord, ycoord: ycoord})"' +
                     '          ng-show="addingNote"' +
                     '          class="add-note"></add-note></div>';
            element = $compile(angular.element(html))(scope);
        }));

        it('(the save changes button) should be disabled when note is empty', function () {
            scope.$digest();
            scope.note = 'Note 001';
            scope.$digest();
            expect(element.find('button:disabled').length).toEqual(0);
        });

        it('(the save changes button) should invoke postNote method', function () {
            scope.$digest();
            element.find('button:eq(0)').click();
            expect(scope.postNote).toHaveBeenCalled();
        });

        it('(the cancel button) should hide the "addnote" window', function () {
            scope.$digest();
            expect(element.find('button:disabled').length).toEqual(1);
            scope.note = 'Note 002';
            scope.$digest();
            expect(element.find('textarea').val().length).not.toEqual(0);
            expect(element.find('button:disabled').length).toEqual(0);
            element.find('button:eq(1)').click();
            expect(element.is(':visible')).toBe(false);
            expect(element.find('button:disabled').length).toEqual(0);
        });
    });
});
