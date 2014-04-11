'use strict';

// Declaring the app
var ptoApp = angular.module("ptoApp", []);

// Gallery controller
ptoApp.controller("galleryCtrl", function($scope) {

  /** Init datas **/
  $scope.galleries = [
    {'title': 'Gallery 1'},
    {'title': 'Gallery 2'},
    {'title': 'Gallery 3'}
  ];

  /**
   * Adding a new gallery.
   *
   */
  $scope.add = function() {
  	if($scope.galleryTitle != "") {
      $scope.galleries.push({
      	title: $scope.galleryTitle
      });
      $scope.galleryTitle = "";
    }
  }

});
