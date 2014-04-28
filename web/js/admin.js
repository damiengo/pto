'use strict';

// Globals
var api = "http://localhost/pto/web/api.php";

// Declaring the app
var ptoApp = angular.module("ptoApp", ["config", "flow"]);

// Configuration
ptoApp.config(["flowFactoryProvider", "ENV", function (flowFactoryProvider, ENV) {
  flowFactoryProvider.defaults = {
    target: ENV.api+"/admin/upload"
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
ptoApp.controller("UserCtrl", ["$scope", "$http", "$window", "UserService", "ENV", function ($scope, $http, $window, userService, ENV) {
  $scope.user    = {username: "", password: ""};
  $scope.message = "";

  /**
   * User authentication.
   */
  $scope.submit  = function () {
    $http
      .post(ENV.api+"/admin/authenticate", $scope.user)
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
ptoApp.controller("galleryCtrl", ["$scope", "$http", "UserService", "ENV", function($scope, $http, userService, ENV) {

  /** Images list **/
  $scope.imagesList = [];

  /** Selected gallery **/
  $scope.selectedGallery = null;

  /** Flow uploader **/
  $scope.uploader = {};

  /** Init galleries **/
  $http.get(ENV.api+"/admin/galleries")
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
      $http.post(ENV.api+"/admin/gallery", {title: $scope.galleryTitle})
        .success(function(data) {
          $scope.galleries.push({
            id:       data.id, 
      	    title:    data.title, 
            slug:     data.slug,
            password: ""
          });
          $scope.galleryTitle = "";
        })
        .error(function() {});
    }
  };

  /**
   * Delete current gallery.
   */
  $scope.delete = function() {
    if($scope.selectedGallery != null) {
      $http.post(ENV.api+"/admin/delete_gallery", {id: $scope.selectedGallery.id})
        .success(function() {
          var index = $scope.galleries.indexOf($scope.selectedGallery);
          $scope.galleries.splice(index, 1);
          $scope.selectedGallery = null;
          $("#deleteModal").modal("hide");
        })
        .error();
    }
  }

  /**
   * Get a gallery images.
   *
   * @param gallery
   */
  $scope.images = function(gallery) {
    $scope.imagesList = [];
    $scope.selectedGallery = gallery;
    $http.get(ENV.api+"/admin/images/"+gallery.id)
      .success(function(data, status) {
        $scope.imagesList = data;
      })
      .error(function(data, status) {});
  };

  /**
   * Add images.
   */
  $scope.upload = function() {
    $scope.uploader.flow.opts.query = {galleryId: $scope.selectedGallery.id};
    $scope.uploader.flow.on("complete", function() {
      $scope.images($scope.selectedGallery);
    });
    $scope.uploader.flow.upload();
  };

  /**
   * Updating gallery password.
   *
   * @param gallery
   */
  $scope.updatePassword = function(gallery) {
    $http.post(ENV.api+"/admin/gallery_password", {id: gallery.id, password: gallery.password})
      .success(function(data) {
        
      })
      .error();
  };

  /**
   * Returns the thumbnail path of an image.
   *
   * @param The image path
   *
   * @return The path
   */
  $scope.getImageThumbnailPath = function(imageName) {
    return ENV.uploads + "thumbnail/" + $scope.selectedGallery.id + "/" + imageName;
  }

  /**
   * Returns the URL for the client gallery.
   *
   * @param gallery
   *
   * @return String
   */
  $scope.getClientGalleryLink = function(gallery) {
    if(gallery != null) {
      return ENV.photos_url + "#" + gallery.slug;
    }

    return "";
  };

  /**
   * Has access.
   */
  $scope.isLogged = function() {
    return userService.isLogged;
  };

}]);
