'use strict';

var app = angular.module('wallsApp', [
    'wallsApp.controllers',
    'wallsApp.services',
    'wallsApp.directives',
    'ngSanitize',
    'ngDragDrop'
]);

app.constant('CONFIG', window.CONFIG);
delete window.CONFIG;
