'use strict';

var app = angular.module('wallsApp.services', []);

app.service('wallsSrv', [
    '$http', '$q', 'CONFIG',
    function ($http, $q, config) {
        var url = config.baseurl + config.api + '/wall/' + config.instanceid;

        this.getPageOfWalls = function (page, perPage) {
            var deferred = $q.defer();
            $http.get(url + '?limitfrom=' + (page * perPage) + '&limitnum=' + perPage).
                success(function (data) {
                    deferred.resolve(data);
                }).
                error(function (data) {
                    deferred.reject(data);
                });
            return deferred.promise;
        };

        this.deleteWall = function (wallid) {
            var deferred = $q.defer();

            $http.delete(url + '/' + wallid).
                success(function (data) {
                    deferred.resolve(data);
                }).
                error(function (data) {
                    deferred.reject(data);
                });
            return deferred.promise;
        };
    }
]);

app.service('notesSrv', [
    '$http', '$q', 'CONFIG',
    function ($http, $q, config) {
        var url = config.baseurl + config.api + '/wall/' + config.instanceid + '/' + config.wallid + '/note';

        this.getNotes = function () {
            var deferred = $q.defer();
            $http.get(url).
                success(function (data) {
                    deferred.resolve(data);
                }).
                error(function (data) {
                    deferred.reject(data);
                });
            return deferred.promise;
        };

        this.getNoteText = function (noteid) {
            var deferred = $q.defer();
            $http.get(url + '/text/' + noteid).
                success(function (data) {
                    deferred.resolve(data);
                }).
                error(function (data) {
                    deferred.reject(data);
                });
            return deferred.promise;
        };

        this.postNote = function (note) {
            var deferred = $q.defer();
            $http.post(url, note).
                success(function (data) {
                    deferred.resolve(data);
                }).
                error(function (data) {
                    deferred.reject(data);
                });
            return deferred.promise;
        };

        this.putNote = function (noteid, note) {
            var deferred = $q.defer();
            $http.put(url + '/' + noteid, note).
                success(function (data) {
                    deferred.resolve(data);
                }).
                error(function (data) {
                    deferred.reject(data);
                });
            return deferred.promise;
        };

        this.deleteNote = function (noteid) {
            var deferred = $q.defer();
            $http.delete(url + '/' + noteid).
                success(function (data) {
                    deferred.resolve(data);
                }).
                error(function (data) {
                    deferred.reject(data);
                });
            return deferred.promise;
        };
    }
]);
