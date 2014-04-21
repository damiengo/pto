'use strict';

// Globals
var api = "http://localhost/pto/web/api.php";

// Declaring the app
var ptoApp = angular.module("ptoApp", ['flow']);

// Configuration
ptoApp.config(['flowFactoryProvider', function (flowFactoryProvider) {
  flowFactoryProvider.defaults = {
    target: api+"/admin/upload"
  };
}]);

// User controller
ptoApp.controller("UserCtrl", function ($scope, $http, $window) {
  $scope.user    = {username: "", password: ""};
  $scope.message = "";
  $scope.submit  = function () {
    $http
      .post(api+"/admin/authenticate", $scope.user)
      .success(function (data, status, headers, config) {
        $window.sessionStorage.token = data.token;
        $scope.user.authenticated = true;
        $scope.message = 'Welcome';
      })
      .error(function (data, status, headers, config) {
        // Erase the token if the user fails to log in
        delete $window.sessionStorage.token;
        $scope.user.authenticated = false;

        // Handle login errors here
        $scope.message = 'Error: Invalid user or password';
      });
  };
});

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
