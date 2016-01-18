'use strict';

describe('viewNote', function () {
    var config, element, scope, rootScope, $timeout;

    beforeEach(function () {
        config = {
            baseurl: '',
            canManage: true
        };
    });

    beforeEach(angular.mock.module('wallsApp.directives', ['$provide',
        function ($provide) {
            $provide.value('CONFIG', config);
        }
    ]));

    beforeEach(angular.mock.module('ngSanitize'));

    beforeEach(inject(function ($templateCache, _$timeout_) {
        $timeout = _$timeout_;

        // put the directive's template in the template cache
        var tmpl =
            '<div>' +
            '   <span class="userpicture" ng-bind-html="note.userpicture"></span>' +
            '   <div>' +
            '       <div ng-show="isEditing" class="input">' +
            '           <textarea ng-model="note.note"></textarea>' +
            '           <div class="clearfix"></div>' +
            '           <button ng-disabled="note.note.length == 0" ng-click="saveChanges({ id: note.id, note: note.note, xcoord: note.xcoord, ycoord: note.ycoord })">savechanges</button>' +
            '           <button ng-click="stopEditing()">cancel</button>' +
            '           <button ng-click="deleteNote(note.id)">delete</button>' +
            '       </div>' +
            '       <div ng-hide="isEditing" class="output">' +
            '           <span class="user" ng-bind="note.userfullname"></span>' +
            '           <span class="text" ng-bind-html="note.note | linky:\'_blank\'"></span>' +
            '       </div>' +
            '   </div>' +
            '</div>';
        $templateCache.put(config.baseurl + '/partials/viewNote.twig', tmpl);
    }));

    describe('general behaviour', function () {
        beforeEach(inject(function ($rootScope, $compile) {
            // create scope
            rootScope = $rootScope;
            scope = $rootScope.$new();
            scope.note = {
                id: 1,
                note: 'Note 001',
                userfullname: 'Admin User',
                userpicture: '<img width="40" height="40" class="userpicture" title="Admin User" alt="Admin User" src="" >',
                is_owner: true
            };

            scope.clicks = 0;

            scope.editingId = null;
            scope.startEditing = jasmine.createSpy().and.callFake(function (note) {
                scope.editingId = note.id;
            });
            scope.stopEditing = jasmine.createSpy().and.callFake(function () {
                scope.editingId = null;
            });
            scope.putNote = jasmine.createSpy().and.callFake(function () {
                scope.editingId = null;
            });
            scope.deleteNote = jasmine.createSpy();
            scope.getNotes = jasmine.createSpy();

            // compile html
            var html =
                '<view-note note="note"' +
                '           can-manage="canManage"' +
                '           is-editing="editingId == note.id"' +
                '           start-editing="startEditing(note)"' +
                '           stop-editing="stopEditing()"' +
                '           delete-note="deleteNote(note.id)"' +
                '           save-changes="putNote(id, { note: note, xcoord: xcoord, ycoord: ycoord })"' +
                '           class="view-note" ' +
                '           jqyoui-draggable ' +
                '           data-jqyoui-options="{ containment: \'parent\' }"' +
                '           data-drag="note.is_owner || canManage"></view-note>';
            element = $compile(angular.element(html))(scope);
        }));

        it('should render the view with userfullname', function () {
            scope.$digest();
            expect(element.html()).toContain(scope.note.userfullname);
        });

        it('should render the view with note', function () {
            scope.$digest();
            expect(element.html()).toContain(scope.note.note);
        });

        it('should render a string with a valid url as a link', function () {
            scope.note.note = 'A note with a link to http://www.example.com';
            scope.$digest();
            expect(element.find('.text').html()).toContain('<a target="_blank" href="http://www.example.com">http://www.example.com</a>');
        });

        it('should render a user picture', function () {
            scope.$digest();
            expect(element.find('.userpicture').html()).toContain('class="userpicture" title="' + scope.note.userfullname + '" alt="' + scope.note.userfullname + '" src=""');
        });

        it('two single clicks should trigger edit fields', function () {
            scope.$digest();
            expect(element.find('div.input.ng-hide').length).toEqual(1);
            expect(element.find('div.output.ng-hide').length).toEqual(0);
            element.find('div:first').click();
            element.find('div:first').click();
            scope.$digest();
            expect(element.find('div.input.ng-hide').length).toEqual(0);
            expect(element.find('div.output.ng-hide').length).toEqual(1);
            expect(scope.startEditing).toHaveBeenCalled();
        });

        it('doubleclick should not trigger edit fields when not the owner', function () {
            scope.note.is_owner = false;
            scope.$digest();
            var e = $.Event('dblclick');
            element.find('div:first').trigger(e);
            expect(element.find('div.input.ng-hide').length).toEqual(1);
            expect(element.find('div.output.ng-hide').length).toEqual(0);
        });

        it('Two single clicks should trigger edit fields when not the owner but admin', function () {
            scope.note.is_owner = false;
            scope.canManage = true;
            scope.$digest();
            element.click();
            element.click();
            scope.$digest();
            expect(element.find('div.input.ng-hide').length).toEqual(0);
            expect(element.find('div.output.ng-hide').length).toEqual(1);
        });

        it('should set the variable clicks to 0 after x milliseconds', function () {
            scope.$digest();
            element.click();
            $timeout.flush(300);
            expect(scope.clicks).toBe(0);
        });

        it('should stop editing when broadcast has been received (clicked outside element) and in editing mode', function () {
            scope.$digest();
            element.click();
            element.click();
            scope.$digest();
            scope.editingId = 1;
            scope.note.note = 'Note 001 changed';
            expect(element.find('div.input.ng-hide').length).toEqual(0);
            expect(element.find('div.output.ng-hide').length).toEqual(1);
            spyOn(scope, '$broadcast').and.callThrough();
            var e = $.Event('click');
            scope.$broadcast('sendClickEvent', e);
            expect(scope.$broadcast).toHaveBeenCalled();
            expect(scope.putNote).toHaveBeenCalled();
            scope.$digest();
            expect(element.find('div.input.ng-hide').length).toEqual(1);
            expect(element.find('div.output.ng-hide').length).toEqual(0);
        });

        it('should stop editing when broadcast has been received (clicked outside element) and NOT in editing mode', function () {
            scope.$digest();
            var e = $.Event('dblclick');
            element.find('div:first').trigger(e);
            scope.editingId = null;
            scope.$digest();
            spyOn(scope, '$broadcast').and.callThrough();
            e = $.Event('click');
            scope.$broadcast('sendClickEvent', e);
            expect(scope.$broadcast).toHaveBeenCalled();
            expect(scope.putNote).not.toHaveBeenCalled();
        });

        it('should do nothing when broadcast has been received (clicked outside element) and nothing has changed', function () {
            scope.$digest();
            scope.editingId = 1;
            scope.$digest();
            expect(element.find('div.input.ng-hide').length).toEqual(0);
            expect(element.find('div.output.ng-hide').length).toEqual(1);
            var e = $.Event('click');
            scope.$broadcast('sendClickEvent', e);
            scope.$digest();
            expect(element.find('div.input.ng-hide').length).toEqual(1);
            expect(element.find('div.output.ng-hide').length).toEqual(0);
            expect(scope.stopEditing).toHaveBeenCalled();
        });

        it('should call deleteNote when delete button is clicked', function () {
            scope.$digest();
            element.find('div:first').click();
            element.find('.input button:eq(2)').click();
            expect(scope.deleteNote).toHaveBeenCalled();
        });

        it('should start dragging when clicked and editing = false', function () {
            scope.is_owner = true;
            scope.editingId = null;
            scope.$digest();
            spyOn(rootScope, '$broadcast');
            element.trigger('dragstart');
            expect(rootScope.$broadcast).toHaveBeenCalled();
        });

        it('should start dragging when clicked and editing = true', function () {
            scope.is_owner = true;
            scope.editingId = 1;
            scope.$digest();
            element.trigger('dragstart');
            expect(scope.startEditing).not.toHaveBeenCalled();
        });

        it('should stop dragging when released', function () {
            scope.$digest();
            element.trigger('dragstop');
            expect(scope.xcoord).not.toBe(null);
            expect(scope.ycoord).not.toBe(null);
        });
    });
});
