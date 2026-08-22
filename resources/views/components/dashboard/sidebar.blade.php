@php
    $themeBase = 'DashboardKit-main';
@endphp

<nav class="pc-sidebar ">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('dashboard') }}" class="b-brand">
                <img src="{{ !empty($webCustomization['sidebarLogoUrl']) ? $webCustomization['sidebarLogoUrl'] : asset('DashboardKit-main/images/logo.svg') }}" alt="" class="logo logo-lg" onerror="this.onerror=null; this.src='{{ asset('DashboardKit-main/images/logo.svg') }}';" style="max-width: 100%; max-height: 50px; width: auto; height: auto; object-fit: contain;">
                <img src="{{ !empty($webCustomization['sidebarLogoUrl']) ? $webCustomization['sidebarLogoUrl'] : asset('DashboardKit-main/images/logo-sm.svg') }}" alt="" class="logo logo-sm" onerror="this.onerror=null; this.src='{{ asset('DashboardKit-main/images/logo-sm.svg') }}';" style="max-width: 100%; max-height: 35px; width: auto; height: auto; object-fit: contain;">
            </a>
        </div>
        <div class="navbar-content">
            <ul class="pc-navbar">
                <li class="pc-item pc-caption">
                    <label>Navigation</label>
                </li>

                @if(auth()->user()?->hasPermission('dashboard'))
                    <li class="pc-item">
                        <a href="{{ route('dashboard') }}" class="pc-link ">
                            <span class="pc-micon"><i class="material-icons-two-tone">home</i></span>
                            <span class="pc-mtext">Dashboard</span>
                        </a>
                    </li>
                @endif

                                @auth
                    @if(auth()->user()?->hasAnyRole([\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_GUDANG]))
                        <li class="pc-item pc-caption">
                            <label>Gudang</label>
                        </li>
                        <li class="pc-item pc-hasmenu">
                            <a href="javascript:void(0);" class="pc-link">
                                <span class="pc-micon">
                                    <i class="material-icons-two-tone">settings</i>
                                </span>
                                <span class="pc-mtext">Barang Masuk</span>
                                <span class="pc-arrow">
                                    <i class="material-icons-two-tone text-white">chevron_right</i>
                                </span>
                            </a>

                            <ul class="pc-submenu">
                                <li class="pc-item">
                                    <a href="{{ route('inbound.index') }}" class="pc-link">
                                        <span class="pc-micon">
                                            <i class="material-icons-two-tone">group</i>
                                        </span>
                                        <span class="pc-mtext">List Barang Masuk</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('outbound.index') }}" class="pc-link">
                                        <span class="pc-micon">
                                            <i class="material-icons-two-tone">group</i>
                                        </span>
                                        <span class="pc-mtext">List Barang Keluar</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('gudang-product.index') }}" class="pc-link">
                                        <span class="pc-micon">
                                            <i class="material-icons-two-tone">group</i>
                                        </span>
                                        <span class="pc-mtext">Stok Barang</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('rak.index') }}" class="pc-link">
                                        <span class="pc-micon">
                                            <i class="material-icons-two-tone">admin_panel_settings</i>
                                        </span>
                                        <span class="pc-mtext">Rak</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if(auth()->user()?->hasAnyRole([\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_SALES]))
                         <li class="pc-item">
                                <a href="{{ route('warehouse-task.index') }}" class="pc-link ">
                                    <span class="pc-micon"><i class="material-icons-two-tone">computer</i></span>
                                    <span class="pc-mtext">Warehouse Tasks</span>
                                </a>
                            </li>
                    @endif
                    @if(auth()->user()?->hasAnyRole([\App\Models\User::ROLE_SUPER_ADMIN]))
                        <li class="pc-item pc-caption">
                            <label>Customer</label>
                        </li>
                        <li class="pc-item pc-hasmenu">
                            <a href="javascript:void(0);" class="pc-link">
                                <span class="pc-micon">
                                    <i class="material-icons-two-tone">supervised_user_circle</i>
                                </span>
                                <span class="pc-mtext">Customer Management</span>
                                <span class="pc-arrow">
                                    <i class="material-icons-two-tone text-white">chevron_right</i>
                                </span>
                            </a>

                            <ul class="pc-submenu">
                                <li class="pc-item">
                                    <a href="{{ route('master-customer.index') }}" class="pc-link">
                                        <span class="pc-micon">
                                            <i class="material-icons-two-tone">group</i>
                                        </span>
                                        <span class="pc-mtext">List Customer</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if(auth()->user()?->hasPermission('sales_finance.view'))
                         <li class="pc-item pc-caption">
                             <label>Sales & Finance</label>
                         </li>
                         <li class="pc-item pc-hasmenu">
                             <a href="javascript:void(0);" class="pc-link">
                                 <span class="pc-micon"><i class="material-icons-two-tone">request_quote</i></span>
                                 <span class="pc-mtext">Sales</span>
                                 <span class="pc-arrow">
                                     <i class="material-icons-two-tone text-white">chevron_right</i>
                                 </span>
                             </a>
                             <ul class="pc-submenu">
                                  <li class="pc-item">
                                      <a href="{{ route('sales-finance.index') }}" class="pc-link">
                                          <span class="pc-micon"><i class="material-icons-two-tone">receipt_long</i></span>
                                          <span class="pc-mtext">Order & Invoice</span>
                                      </a>
                                  </li>
                                  <li class="pc-item">
                                      <a href="{{ route('sales-finance.pre-orders.index') }}" class="pc-link">
                                          <span class="pc-micon"><i class="material-icons-two-tone">schedule</i></span>
                                          <span class="pc-mtext">Pre-Order (Indent)</span>
                                      </a>
                                  </li>
                                  <li class="pc-item">
                                      <a href="{{ route('returs.index') }}" class="pc-link">
                                          <span class="pc-micon"><i class="material-icons-two-tone">assignment_return</i></span>
                                          <span class="pc-mtext">Retur Barang</span>
                                      </a>
                                  </li>
                                 <li class="pc-item">
                                     <a href="{{ route('internal-invoices.index') }}" class="pc-link">
                                         <span class="pc-micon"><i class="material-icons-two-tone">swap_horiz</i></span>
                                         <span class="pc-mtext">Invoice Internal</span>
                                     </a>
                                 </li>
                             </ul>
                         </li>

                         <li class="pc-item pc-caption">
                             <label>Finance</label>
                         </li>
                         <li class="pc-item pc-hasmenu">
                             <a href="javascript:void(0);" class="pc-link">
                                 <span class="pc-micon"><i class="material-icons-two-tone">account_balance</i></span>
                                 <span class="pc-mtext">Keuangan / Finance</span>
                                 <span class="pc-arrow">
                                     <i class="material-icons-two-tone text-white">chevron_right</i>
                                 </span>
                             </a>
                             <ul class="pc-submenu">
                                 <li class="pc-item">
                                     <a href="{{ route('finance.index') }}" class="pc-link">
                                         <span class="pc-micon"><i class="material-icons-two-tone">dashboard</i></span>
                                         <span class="pc-mtext">Overview</span>
                                     </a>
                                 </li>
                                 <li class="pc-item">
                                     <a href="{{ route('finance.ar') }}" class="pc-link">
                                         <span class="pc-micon"><i class="material-icons-two-tone">account_balance_wallet</i></span>
                                         <span class="pc-mtext">Piutang (AR)</span>
                                     </a>
                                 </li>
                                 <li class="pc-item">
                                     <a href="{{ route('finance.ap') }}" class="pc-link">
                                         <span class="pc-micon"><i class="material-icons-two-tone">payment</i></span>
                                         <span class="pc-mtext">Hutang (AP)</span>
                                     </a>
                                 </li>
                                 <li class="pc-item">
                                     <a href="{{ route('finance.report') }}" class="pc-link">
                                         <span class="pc-micon"><i class="material-icons-two-tone">bar_chart</i></span>
                                         <span class="pc-mtext">Laporan Finance</span>
                                     </a>
                                 </li>
                             </ul>
                         </li>
                     @endif

                    @if(auth()->user()?->hasPermission('suppliers.view'))
                        <li class="pc-item pc-caption">
                            <label>Master</label>
                        </li>
                        <li class="pc-item pc-hasmenu">
                            <a href="javascript:void(0);" class="pc-link">
                                <span class="pc-micon"><i class="material-icons-two-tone">local_shipping</i></span>
                                <span class="pc-mtext">Supplier</span>
                                <span class="pc-arrow">
                                    <i class="material-icons-two-tone text-white">chevron_right</i>
                                </span>
                            </a>
                            <ul class="pc-submenu">
                                <li class="pc-item">
                                    <a href="{{ route('suppliers.index') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">dashboard</i></span>
                                        <span class="pc-mtext">Overview</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('suppliers.index') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">business</i></span>
                                        <span class="pc-mtext">Data Vendor</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('suppliers.products') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">inventory_2</i></span>
                                        <span class="pc-mtext">Barang Supplier</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('suppliers.contacts') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">contacts</i></span>
                                        <span class="pc-mtext">Kontak Supplier</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('suppliers.purchases') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">receipt_long</i></span>
                                        <span class="pc-mtext">Riwayat Pembelian</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('suppliers.payment-terms') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">payments</i></span>
                                        <span class="pc-mtext">Termin Pembayaran</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('supplier-po.index') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">description</i></span>
                                        <span class="pc-mtext">Purchase Order (PO)</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="pc-item pc-hasmenu">
                            <a href="javascript:void(0);" class="pc-link">
                                <span class="pc-micon"><i class="material-icons-two-tone">widgets</i></span>
                                <span class="pc-mtext">Master Barang</span>
                                <span class="pc-arrow">
                                    <i class="material-icons-two-tone text-white">chevron_right</i>
                                </span>
                            </a>
                            <ul class="pc-submenu">
                                <li class="pc-item">
                                    <a href="{{ route('products.index') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">inventory</i></span>
                                        <span class="pc-mtext">Master Product</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('brands.index') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">style</i></span>
                                        <span class="pc-mtext">Master Brand</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('categories.index') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">category</i></span>
                                        <span class="pc-mtext">Master Kategori</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('sub-categories.index') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">layers</i></span>
                                        <span class="pc-mtext">Master Sub Kategori</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('variants.index') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">tune</i></span>
                                        <span class="pc-mtext">Master Varian</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="pc-item">
                            <a href="{{ route('rekening-banks.index') }}" class="pc-link">
                                <span class="pc-micon"><i class="material-icons-two-tone">account_balance</i></span>
                                <span class="pc-mtext">Master Rekening Bank</span>
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()?->hasPermission('users.view') || auth()->user()?->hasPermission('roles.manage') || auth()->user()?->hasPermission('activity_logs.view') || auth()->user()?->hasPermission('sessions.manage'))
                        <li class="pc-item pc-caption">
                            <label>Management</label>
                        </li>
                        <li class="pc-item pc-hasmenu">
                            <a href="javascript:void(0);" class="pc-link">
                                <span class="pc-micon">
                                    <i class="material-icons-two-tone">admin_panel_settings</i>
                                </span>
                                <span class="pc-mtext">User & Role Management</span>
                                <span class="pc-arrow">
                                    <i class="material-icons-two-tone text-white">chevron_right</i>
                                </span>
                            </a>

                            <ul class="pc-submenu">
                                @if(auth()->user()?->hasPermission('users.view'))
                                    <li class="pc-item">
                                        <a href="{{ route('users.index') }}" class="pc-link">
                                            <span class="pc-micon">
                                                <i class="material-icons-two-tone">group</i>
                                            </span>
                                            <span class="pc-mtext">User Management</span>
                                        </a>
                                    </li>
                                @endif

                                @if(auth()->user()?->hasPermission('roles.manage'))
                                    <li class="pc-item">
                                        <a href="{{ route('roles.index') }}" class="pc-link">
                                            <span class="pc-micon">
                                                <i class="material-icons-two-tone">admin_panel_settings</i>
                                            </span>
                                            <span class="pc-mtext">Role Management</span>
                                        </a>
                                    </li>
                                @endif

                                @if(auth()->user()?->hasPermission('activity_logs.view'))
                                    <li class="pc-item">
                                        <a href="{{ route('activity-logs.index') }}" class="pc-link">
                                            <span class="pc-micon">
                                                <i class="material-icons-two-tone">history</i>
                                            </span>
                                            <span class="pc-mtext">Activity Log</span>
                                        </a>
                                    </li>
                                @endif

                                @if(auth()->user()?->hasPermission('sessions.manage'))
                                    <li class="pc-item">
                                        <a href="{{ route('sessions.index') }}" class="pc-link">
                                            <span class="pc-micon">
                                                <i class="material-icons-two-tone">devices</i>
                                            </span>
                                            <span class="pc-mtext">Session Management</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                        @if(auth()->user()?->hasPermission('roles.manage'))
                            <li class="pc-item">
                                <a href="{{ route('web-customization.edit') }}" class="pc-link ">
                                    <span class="pc-micon"><i class="material-icons-two-tone">computer</i></span>
                                    <span class="pc-mtext">Web Customization</span>
                                </a>
                            </li>
                        @endif
                    @endif
                @endauth
            </ul>
        </div>
    </div>
</nav>
