
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Registration</h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="/admin_dashboard">Home</a></li>
          <li class="breadcrumb-item active">Registration</li>
        </ol>
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div>
  <div class="register-box" style="margin: 4% auto 5% auto;width:50%;">
  <div class="card-body register-card-body">
    <h2 class="login-box-msg">Register a new user</h2>

    <form action="register" method="post">
      {{csrf_field()}}
      {{-- <div class="input-group mb-3 col-8" style="margin: auto;">
        <input type="text" class="form-control" name="fullname" placeholder="Full name">
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-user"></span>
          </div>
        </div>
      </div> --}}
      <div class="input-group mb-3 col-8" style="margin: auto;">
        <input type="text" class="form-control" name="mobile" placeholder="Mobile">
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-mobile"></span>
          </div>
        </div>
      </div>
      <div class="input-group mb-3 col-8" style="margin: auto;">
        <input type="email" class="form-control" name="email" placeholder="Email">
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-envelope"></span>
          </div>
        </div>
      </div>
      {{-- <div class="input-group mb-3 col-8" style="margin: auto;">
        <select class="form-control" name="mobile">
          <option value="">Select User Role</option>
          <option value="admin">Admin</option>
        </select>
      </div> --}}
      <div class="input-group mb-3 col-8" style="margin: auto;">
        <input type="text" class="form-control" name="username" placeholder="Username">
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-user"></span>
          </div>
        </div>
      </div>
      <div class="input-group mb-3 col-8" style="margin: auto;">
        <input type="password" class="form-control" name="password" placeholder="Password">
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-lock"></span>
          </div>
        </div>
      </div>
      <div class="input-group mb-3 col-8" style="margin: auto;">
        <input type="password" class="form-control" name="re_password" placeholder="Retype password">
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-lock"></span>
          </div>
        </div>
      </div>
      <div class="input-group mb-3 col-8" style="margin: auto;">
        <select name="user_type" class="form-control" ng-model="selectedUserType"
                ng-change="onUserTypeChange()">
            <option value="">--Select a User Type--</option>
            <option name="admin" value="11">Admin</option>
            <option name="dc" value="22">DC</option>

        </select>
        @if($errors->has('user_type'))
            <p class="text-danger">{{$errors->first('user_type')}}</p>
        @endif
    </div>
      <div class="row">
        <div class="col-8">
          {{-- <div class="icheck-primary">
            <input type="checkbox" id="agreeTerms" name="terms" value="agree">
            <label for="agreeTerms">
             I agree to the <a href="#">terms</a>
            </label>
          </div> --}}
        </div>
        <!-- /.col -->
        <div class="col-2">
          <button type="submit" class="btn btn-primary btn-block">Register</button>
        </div>
        <!-- /.col -->
      </div>
    </form>
  </div>
  </div>
  <!-- /.form-box -->
</div><!-- /.card -->
