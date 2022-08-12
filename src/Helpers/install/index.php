<!doctype html>
<html lang="en">
  <head>

    <base href="<?php echo $backtrace; ?>vendor/abstracts/core/src/Helpers/install/">

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Libraries -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,700,900&display=swap" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" crossorigin="anonymous">
    
    <!-- Style -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/meta/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/meta/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/meta/apple-touch-icon.png">
    <link rel="manifest" href="assets/meta/site.webmanifest">
    <link rel="mask-icon" href="assets/meta/safari-pinned-tab.svg" color="#ff0d46">
    <meta name="msapplication-TileColor" content="#ff0d46">
    <meta name="theme-color" content="#ff0d46">

    <title>Install | Abstracts Framework</title>

  </head>
  <body>

    <div class="content full-height">
      
      <div class="container full-width force-center-middle">
        <div class="row align-items-center">
          <div class="col-lg-6 mr-auto">
            <div class="text-center">
              <img class="page-logo" src="assets/images/logo-inverted.svg" alt="Abstracts Framework" />
            </div>
          </div>
          <div class="col-lg-6">
            <div class="box">
              <form class="mb-5" method="post" id="submit" name="submit">
                <h3 class="heading">Site</h3>
                <div class="row">
                  <div class="col-md-12 form-group">
                    <label for="site_name" class="col-form-label">Site Name</label>
                    <input type="text" class="form-control" name="site_name" id="site_name">
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12 form-group">
                    <label for="type" class="col-form-label">Type</label>
                    <select type="text" class="form-control" name="type" id="type">
                      <option value="web">Web</option>
                      <option value="api">API</option>
                    </select>
                  </div>
                </div>
                <h3 class="heading">Creator Account</h3>
                <div class="row">
                  <div class="col-md-12 form-group">
                    <label for="name" class="col-form-label">
                      Name
                    </label>
                    <input type="text" class="form-control" name="name" id="name">
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12 form-group">
                    <label for="username" class="col-form-label">
                      Username
                      <div class="desc" for="password_salt">
                        Username does not allow special characters and white spaces excluding ., _
                      </div>
                    </label>
                    <input type="text" class="form-control" name="username" id="username">
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12 form-group">
                    <label for="password" class="col-form-label">
                      Password
                      <div class="desc" for="password_salt">
                        Password must contain uppercases, lowercases, digits and special characters
                      </div>
                    </label>
                    <input type="password" class="form-control" name="password" id="password">
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12 form-group">
                    <label for="confirm_password" class="col-form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" id="confirm_password">
                  </div>
                </div>
                <h3 class="heading">Database</h3>
                <div class="row">
                  <div class="col-md-12 form-group">
                    <label for="database_host" class="col-form-label">Host</label>
                    <input type="text" class="form-control" name="database_host" id="database_host" placeholder="Ex. localhost">
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12 form-group">
                    <label for="database_table" class="col-form-label">Database Name</label>
                    <input type="text" class="form-control" name="database_table" id="database_table">
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12 form-group">
                    <label for="database_login" class="col-form-label">Login</label>
                    <input type="text" class="form-control" name="database_login" id="database_login">
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12 form-group">
                    <label for="database_password" class="col-form-label">Password</label>
                    <input type="password" class="form-control" name="database_password" id="database_password">
                  </div>
                </div>
                <h3 class="heading">Security</h3>
                <div class="row">
                  <div class="col-md-12 form-group">
                    <label for="password_salt" class="col-form-label">
                      Password Salt
                      <div class="desc" for="password_salt">
                        Salt is string of characters known only to the site added to password before it is hashed, makes your hashed password unique from another site for security improvement
                      </div>
                    </label>
                    <input type="text" class="form-control" name="password_salt" id="password_salt">
                  </div>
                </div>
                <div class="row submit">
                  <div class="col-md-12">
                    <button type="submit" id="btn-install" class="btn btn-block btn-primary rounded-0 py-2 px-4">
                      <i class="btn-icon fa-solid fa-arrow-down"></i>
                      Install
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
    </div>

    <div id="modal-installing" class="modal modal-loading" tabindex="-1" 
    data-bs-backdrop="static" data-bs-keyboard="false"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center">
            <h5 class="modal-title">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              Installing...
            </h5>
            <p id="installing-message">
            </p>
          </div>
        </div>
      </div>
    </div>

    <div id="modal-success" class="modal fade" tabindex="-1"
    data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center">
            <h5 class="modal-title">
              <i class="title-icon success fa-solid fa-circle-check"></i>
            </h5>
            <h5 class="modal-title">
              Successfully installed!
            </h5>
            <p id="success-message">
              Write down <strong>API Credential</strong> to use it with Abstract Client
              <div class="container container-key">
                <div class="row">
                  <div class="col-6 text-start">
                    <strong>API Key</strong>
                  </div>
                  <div class="col-6 text-start" id="api-key">
                  </div>
                </div>
                <div class="row">
                  <div class="col-6 text-start">
                    <strong>API Secret</strong>
                  </div>
                  <div class="col-6 text-start" id="api-secret">
                  </div>
                </div>
              </div>
              <div class="container-important">
                Double check and set permission 777 to these directories <strong>./media</strong>, <strong>./service</strong>
              </div>
              <div class="container-important">
                <span class="badge badge-danger">Important!</span> Remove <strong>./install</strong> directory and set permission of root back to normal (644/755) immediately for security purpose.
              </div>
            </p>
          </div>
          <div class="modal-footer">
            <button id="btn-launch" type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="btn-icon fa-solid fa-arrow-up-right-from-square"></i>
              Launch
            </button>
          </div>
        </div>
      </div>
    </div>

    <div id="modal-error" class="modal fade" tabindex="-1"
    data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center">
            <h5 class="modal-title">
              <i class="title-icon danger fa-solid fa-triangle-exclamation"></i>
            </h5>
            <h5 class="modal-title">
              Unsuccessfully installed
            </h5>
            <p id="error-message">
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="btn-icon fa-solid fa-xmark"></i>
              Close
            </button>
          </div>
        </div>
      </div>
    </div>

    <div id="modal-intro" class="modal fade" tabindex="-1"
    data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center">
            <h5 class="modal-title">
              <i class="title-icon fa-solid fa-lock-open"></i>
            </h5>
            <h5 class="modal-title">
              Before install
            </h5>
            <p id="success-message">
              It is neccessary to set permission 777 to root and these directories <strong>./media</strong>, <strong>./service</strong> before install
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="btn-icon fa-solid fa-check"></i>
              Done
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/js/all.min.js" crossorigin="anonymous"></script>

    <script>
    var submit = function() {
      if ($("#submit").length > 0) {
        $("#btn-install").prop("disabled", false);
        $("#btn-install").text(`Install`);
	      $("#installing-message").text("");
	      $("#error-message").text("");
        $("#btn-launch").unbind();
        $("#submit").validate( {
          rules: {
            site_name: {
              required: true
            },
            type: {
              required: true
            },
            database_host: {
              required: true
            },
            database_table: {
              required: true
            },
            database_login: {
              required: true
            },
            name: {
              required: true
            },
            username: {
              required: true,
              minlength: 5,
              maxlength: 100
            },
            password: {
              required: true,
              minlength: 8,
              maxlength: 100
            },
            confirm_password: {
              equalTo : '#password'
            },
            password_salt: {
              required: true
            }
          },
          messages: {
            site_name: {
              required: 'Site Name is required'
            },
            type: {
              required: 'Type is required'
            },
            database_host: {
              required: 'Database Host is required'
            },
            database_table: {
              required: 'Database Name is required'
            },
            database_login: {
              required: 'Database Login is required'
            },
            name: {
              required: 'Name is required'
            },
            username: {
              required: 'Username is required',
              minlength: 'Username is shorter than minimum length of strings at 5 characters',
              maxlength: 'Username is longer than maximum length of strings at 100 characters'
            },
            password: {
              required: 'Password is required',
              minlength: 'Password is shorter than minimum length of strings at 8 characters',
              maxlength: 'Password is longer than maximum length of strings at 100 characters'
            },
            confirm_password: {
              equalTo : 'Password is mismatched'
            },
            password_salt: {
              required: 'Password Salt is required'
            }
          },
          submitHandler: function(form) {
            $("#btn-install").prop("disabled", true);
            $("#btn-install").text(`Installing...`);
	          $("#installing-message").text("Installing directories");
            $.ajax({
              url: "services/directories.php",
              type: "POST",
              cache: false,
              dataType: "json",
              success: function(response) {
                      console.log(response);
			          if (response.status) {
                  $("#installing-message").text("Installing configuration");
                  $.ajax({
                    url: "services/config.php",
                    type: "POST",
                    cache: false,
                    dataType: "json",
                    data: {
                      site_name: $(form).find('input[name="site_name"]').val(),
                      password_salt: $(form).find('input[name="password_salt"]').val(),
                      database_host: $(form).find('input[name="database_host"]').val(),
                      database_table: $(form).find('input[name="database_table"]').val(),
                      database_login: $(form).find('input[name="database_login"]').val(),
                      database_password: $(form).find('input[name="database_password"]').val()
                    },
                    success: function(response) {
                      console.log(response);
                      if (response.status) {
                        $("#installing-message").text("Installing database");
                        $.ajax({
                          url: "services/database.php",
                          type: "POST",
                          cache: false,
                          dataType: "json",
                          data: {
                            name: $(form).find('input[name="name"]').val(),
                            username: $(form).find('input[name="username"]').val(),
                            password: $(form).find('input[name="password"]').val(),
                            password_salt: $(form).find('input[name="password_salt"]').val(),
                            database_host: $(form).find('input[name="database_host"]').val(),
                            database_table: $(form).find('input[name="database_table"]').val(),
                            database_login: $(form).find('input[name="database_login"]').val(),
                            database_password: $(form).find('input[name="database_password"]').val()
                          },
                          success: function(response) {
                      console.log(response);
                            $("#btn-install").prop("disabled", true);
                            $("#btn-install").text(`Installed`);
                            $("#modal-installing").modal("hide");
                            if (response.status) {
                              $("#installing-message").text("Installing configuration for server");
                              var responseAPI = response.api;
                              $.ajax({
                                url: "services/server.php",
                                type: "POST",
                                cache: false,
                                dataType: "json",
                                data: {
                                  type: $(form).find('input[name="type"]').val(),
                                },
                                success: function(response) {
                      console.log(response);
                                  if (response.status) {
                                    if (responseAPI) {
                                      $("#api-key").text(responseAPI.key ? responseAPI.key : '');
                                      $("#api-secret").text(responseAPI.secret ? responseAPI.secret : '');
                                    }
                                    $("#btn-launch").click(function () {
                                      location.href = response.data;
                                    });
                                    $("#modal-success").modal("show");
                                  } else {
                                    $("#error-message").text(`${response.message} (Configuration for server)`);
                                    $("#modal-error").modal("show");
                                    $("#btn-install").prop("disabled", false);
                                    $("#btn-install").text(`Install`);
                                  }
                                },
                                error: function(XMLHttpRequest, textStatus, errorThrown) {
                                  $("#error-message").text(`${errorThrown} (Configuration for server)`);
                                  $("#modal-error").modal("show");
                                  $("#btn-install").prop("disabled", false);
                                  $("#btn-install").text(`Install`);
                                }
                              });
                            } else {
                              $("#error-message").text(`${response.message} (Database)`);
                              $("#modal-error").modal("show");
                              $("#btn-install").prop("disabled", false);
                              $("#btn-install").text(`Install`);
                            }
                          },
                          error: function(XMLHttpRequest, textStatus, errorThrown) {
                            $("#error-message").text(`${errorThrown} (Database)`);
                            $("#modal-error").modal("show");
                            $("#btn-install").prop("disabled", false);
                            $("#btn-install").text(`Install`);
                          }
                        });
                      } else {
                        $("#error-message").text(`${response.message} (Configuration)`);
                        $("#modal-error").modal("show");
                        $("#btn-install").prop("disabled", false);
                        $("#btn-install").text(`Install`);
                      }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                      $("#error-message").text(`${errorThrown} (Configuration)`);
                      $("#modal-error").modal("show");
                      $("#btn-install").prop("disabled", false);
                      $("#btn-install").text(`Install`);
                    }
                  });
                } else {
	                $("#error-message").text(`${response.message} (Directories)`);
                  $("#modal-error").modal("show");
                  $("#btn-install").prop("disabled", false);
                  $("#btn-install").text(`Install`);
                }
              },
              error: function(XMLHttpRequest, textStatus, errorThrown) {
	              $("#error-message").text(`${errorThrown} (Directories)`);
                $("#modal-error").modal("show");
                $("#btn-install").prop("disabled", false);
                $("#btn-install").text(`Install`);
              }
            });
          }
        });
      }
    };
    submit();
    $(document).ready(function() {
      $("#modal-intro").modal("show");
    });
    </script>

  </body>
</html>