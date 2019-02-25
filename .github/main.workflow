workflow "Run tests" {
  on = "push"
  resolves = ["Spec tests", "Unit tests", "Feature tests"]
}

action "Composer install" {
  uses = "pxgamer/composer-action@master"
  args = "install"
}

action "Spec tests" {
  needs = ["Composer install"]
  uses = "./.github/actions/php-action"
  env = {
    APP_KEY = "base64:Z++HhObkg1plehwZlK6RGSRVi4uWPiUGRYFoDOt2/Ig="
    DB_CONNECTION = "sqlite"
    DB_DATABASE = "/tmp/acdc.sqlite"
    LOG_CHANNEL = "errorlog"
    APP_ENV = "testing"
  }
  args = "ci-spec"
  secrets = ["CODECOV_TOKEN"]
}

action "Unit tests" {
  needs = ["Composer install"]
  uses = "./.github/actions/php-action"
  args = "ci-unit"
  secrets = ["CODECOV_TOKEN"]
}

action "Feature tests" {
  needs = ["Composer install"]
  uses = "./.github/actions/php-action"
  env = {
    APP_KEY = "base64:Z++HhObkg1plehwZlK6RGSRVi4uWPiUGRYFoDOt2/Ig="
    DB_CONNECTION = "sqlite"
    DB_DATABASE = "/tmp/acdc.sqlite"
    LOG_CHANNEL = "errorlog"
    APP_ENV = "testing"
  }
  args = "ci-feature"
  secrets = ["CODECOV_TOKEN"]
}
