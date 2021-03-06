<aside class="main-sidebar">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">
          <!-- Sidebar user panel -->
          <div class="user-panel">
            <div class="pull-left image">
            @if(Session::has('image') && Session::get('image')!= '')
              <img src="{{url('/')}}/public/admin_new/{{Session::get('image')}}" class="" alt="User Image">
              @else
              <img src="{{url('/')}}/public/imgx/logo.png" class="" alt="User Image">
            @endif
            </div>
          </div>
          <!-- sidebar menu: : style can be found in sidebar.less -->
          <ul class="sidebar-menu">

            <li class="header">MAIN NAVIGATION</li>
            <li><a href="{{ url('/') }}/dashboard"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{url('/')}}/generate-invoice"><i class="fa fa-th"></i> <span>Create Invoice</span></a></li>
            <li><a href="{{ route('all-invoices') }}"><i class="fa fa-list" aria-hidden="true"></i><span>All Records</span></a></li>
            <li><a href="{{ url('/') }}/profile"><i class="fa fa-user-md"></i> <span>Profile</span></a></li>
            
          </ul>
        </section>
        <!-- /.sidebar -->
      </aside>