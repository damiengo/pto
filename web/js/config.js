'use strict';

/**
 * Application configuration.
 */
angular.module("config", [])
.constant("ENV", {
  "name": "development",
  "api":  "http://localhost/pto/web/api.php"
});
