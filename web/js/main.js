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

// Service
ptoApp.service("UserService", [function() {
  var user = {
    isLogged: false,
    username: ""
  };

  return user;
}]);

// User controller
ptoApp.controller("UserCtrl", ["$scope", "$http", "$window", "UserService", function ($scope, $http, $window, userService) {
  $scope.user    = {username: "", password: ""};
  $scope.message = "";

  /**
   * User authentication.
   */
  $scope.submit  = function () {
    $http
      .post(api+"/admin/authenticate", $scope.user)
      .success(function (data, status, headers, config) {
        $window.sessionStorage.token = data.token;
        userService.isLogged = true;
        $scope.message = 'Welcome';
      })
      .error(function (data, status, headers, config) {
        // Erase the token if the user fails to log in
        delete $window.sessionStorage.token;
        userService.isLogged = false;

        // Handle login errors here
        $scope.message = 'Error: Invalid user or password';
      });
  };

  /**
   * Has log in form
   */
  $scope.isLogged = function() {
    return userService.isLogged;
  };
}]);

// Gallery controller
ptoApp.controller("galleryCtrl", ["$scope", "$http", "UserService", function($scope, $http, userService) {

  /** Images list **/
  $scope.images = [];

  /** Selected gallery id **/
  $scope.selectedGalleryId = null;

  /** Flow uploader **/
  $scope.uploader = {};

  /** Init galleries **/
  $http.get(api+"/admin/galleries")
    .success(function(data, status, headers, config) {
      $scope.galleries = data;
    })
    .error(function(data, status, headers, config) {
    }
  );

  /**
   * Adding a new gallery.
   *
   */
  $scope.add = function() {
    if($scope.galleryTitle != "") {
      $http.post(api+"/admin/gallery", {title: $scope.galleryTitle})
        .success(function() {
          $scope.galleries.push({
      	    title: $scope.galleryTitle
          });
          $scope.galleryTitle = "";
        })
        .error(function() {});
    }
  };

  /**
   * Get a gallery images.
   *
   * @param galleryId
   */
  $scope.images = function(galleryId) {
    $scope.selectedGalleryId = galleryId;
    $http.get(api+"/admin/images/"+galleryId)
      .success(function(data, status) {
        $scope.images = data;
      })
      .error(function(data, status) {});
  }

  /**
   * Add images.
   */
  $scope.upload = function() {
    $scope.uploader.flow.opts.query = {galleryId: $scope.selectedGalleryId};
    $scope.uploader.flow.upload();
  }

  /**
   * Has access.
   */
  $scope.isLogged = function() {
    return userService.isLogged;
  };

}]);
