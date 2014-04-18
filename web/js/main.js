'use strict';

// Declaring the app
var ptoApp = angular.module("ptoApp", ['flow']);

// Configuration
ptoApp.config(['flowFactoryProvider', function (flowFactoryProvider) {
  flowFactoryProvider.defaults = {
    target: 'http://localhost:8888/admin/upload'
  };
  flowFactoryProvider.on('catchAll', function (event) {
    console.log('catchAll', arguments);
  });
  // Can be used with different implementations of Flow.js
  // flowFactoryProvider.factory = fustyFlowFactory;
}]);

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
