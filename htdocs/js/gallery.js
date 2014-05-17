'use strict';

// Declaring the app
var galleryApp = angular.module("galleryApp", ["wu.masonry", "config"]);

// Gallery controller
galleryApp.controller("galleryCtrl", ["$scope", "$http", "ENV", function($scope, $http, ENV) {

  /** Images list **/
  $scope.imagesList = [];

  /** Selected gallery **/
  $scope.selectedGallery = null;

  /** URL hash value **/
  $scope.urlHash = window.location.hash;

  /**
   * Check gallery password.
   *
   * @param password
   */
  $scope.checkPassword = function(password) {
    var galleryName = $scope.urlHash.substr(1);
    $http.get(ENV.api+"/gallery/check_password/"+galleryName+"/"+password)
      .success(function(data) {
        $scope.selectedGallery = data.gallery;
        $scope.loadImages($scope.selectedGallery);
      })
      .error(function(data) {});

    return false;
  };

  /**
   * Loads the gallery images.
   *
   * @param Gallery to load
   */
  $scope.loadImages = function(gallery) {
    $http.get(ENV.api+"/gallery/images/"+gallery.id)
      .success(function(data) {
        $scope.imagesList = data.images;
      })
      .error(function(data) {
      });
  }

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
   * Returns the original path of an image.
   *
   * @param The image path
   *
   * @return The path
   */
  $scope.getImageOriginalPath = function(imageName) {
    return ENV.uploads + "original/" + $scope.selectedGallery.id + "/" + imageName;
  }

}])
