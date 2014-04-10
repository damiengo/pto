'use strict';

// Declaring the app
var ptoApp = angular.module("ptoApp", []);

// Gallery controller
ptoApp.controller("galleryCtrl", function($scope) {

  $scope.galleries = [
    {'title': 'Gallery 1'},
    {'title': 'Gallery 2'},
    {'title': 'Gallery 3'}
  ];

});
