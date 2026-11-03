<?php
  if(!$this->session->userdata('is_logged_in')) {
    redirect(config_item('portal_url'));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title><?= html_escape(config_item('app_name') . ' - ' . config_item('app_long_name')); ?></title>
  <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
  <link rel="icon" href="<?=base_url("assets/img/note-icon.png")?>" type="image/x-icon" />

  <!-- Fonts and icons -->
  <script src="<?=base_url('assets/js/plugin/webfont/webfont.min.js');?>"></script>
  <script>

    WebFont.load({
      google: {
        "families": ["Poppins:400,500,600,700,800,900"]
      },
      custom: {
        "families": ["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands",
          "simple-line-icons", "luckiestguy"
        ],
        urls: ['<?=base_url('assets/css/fonts.min.css');?>']
      },
      active: function () {
        sessionStorage.fonts = true;
      }
    });
  </script>
  <!-- CSS Files -->
  <link rel="stylesheet" href="<?=base_url('assets/css/bootstrap.min.css');?>">
  <link rel="stylesheet" href="<?=base_url('assets/css/atlantis.css');?>">
  <link rel="stylesheet" href="<?=base_url('assets/css/dragula.css');?>">
  <link rel="stylesheet" href="<?=base_url('assets/css/dragula.min.css');?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/main.css?v=' . (file_exists(FCPATH . 'assets/css/main.css') ? filemtime(FCPATH . 'assets/css/main.css') : time())); ?>">
  <!-- <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css"> -->
      <link rel="stylesheet" href="<?=base_url('assets/css/select2-bootstrap4.min.css');?>">
  <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />


</head>


<body>
  <div class="wrapper overlay-sidebar">

      <div class="main-header dynamic-borderbottom " >
      <!-- Logo Header -->
      <div class="logo-header bg-primary">

        <a href="<?=base_url('dashboard/');?>" class="logo erlp-brand" title="<?= html_escape(config_item('app_long_name')); ?>">
          <img src="<?=base_url(config_item('app_logo_header') . '?v=' . (is_file(FCPATH . config_item('app_logo_header')) ? filemtime(FCPATH . config_item('app_logo_header')) : time()));?>" alt="<?= html_escape(config_item('app_name') . ' - ' . config_item('app_long_name')); ?>" class="navbar-brand erlp-brand-logo">
        </a>
        <button class="navbar-toggler sidenav-toggler ml-auto" type="button" data-toggle="collapse"
          data-target="collapse" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon">
            <i class="icon-menu"></i>
          </span>
        </button>

        <div class="nav-toggle">
          <button class="btn btn-toggle sidenav-overlay-toggler">
            <i class="icon-menu"></i>
          </button>
        </div>
      </div>
      <!-- End Logo Header -->

      <!-- Navbar Header -->
      <nav class="navbar navbar-header navbar-expand-lg bg-primary">
        <div class="container-fluid">
          <ul class="navbar-nav topbar-nav ml-md-auto align-items-center ">
              <li class="nav-item kna-gsearch-wrap">
                <div class="kna-gsearch">
                  <i class="fas fa-search kna-gsearch-icon"></i>
                  <input type="text" id="kna-gsearch-input" class="kna-gsearch-input" placeholder="Search CA, LQ, RMB no. or name…" autocomplete="off">
                  <div class="kna-gsearch-results d-none" id="kna-gsearch-results"></div>
                </div>
              </li>
              <li class="nav-item dropdown hidden-caret">
                <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="fa fa-bell"></i>
                  <span class="notification notification-count d-none" id="notifBadgeCount"></span>
                </a>
                <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">
                  <li>
                    <div class="dropdown-title">Notifications (<span id="notifTitleCount">0</span>)</div>
                  </li>
                  <li>
                    <div class="notif-scroll">
                      <div class="notif-center" id="notif-center">
                        <div class="text-center kna-small text-muted py-3" id="notifEmptyState">No recent notifications.</div>
                      </div>
                    </div>
                  </li>
                </ul>
              </li>
  
          </ul>
        </div>
      </nav>


      <!-- End Navbar -->
    </div>



    <!-- Sidebar -->

    <div class="sidebar  sidebar-style-2 ">
      <!-- "scrollbar"/"scrollbar-inner" classes intentionally omitted:
           they trigger the Atlantis template's jQuery Scrollbar plugin
           (atlantis.min.js), which fakes scrolling via a transformed
           inner wrapper instead of real browser scrolling. That broke
           position:sticky on the Dashboard/Modules nav items, since
           sticky depends on genuine native scroll. Real scrolling is
           already handled natively by .nav.nav-primary's own
           overflow-y:auto below, so the plugin isn't needed here. -->
      <div class="sidebar-wrapper" >
        <div class="sidebar-content" >
          <div class="user">
            <div class="avatar-sm float-left mr-2">
              <img src="<?=base_url('assets/img/avatar-nb.png');?>" alt="..." class="avatar-img rounded-circle">
            </div>
            <div class="info" style="white-space: normal; word-wrap: break-word;">
              <a href="#" aria-expanded="false">
                <span>
                  <?=$this->session->userdata('user_info')['lastname'] . ' ' . $this->session->userdata('user_info')['firstname'];?>
                  <span class="user-level">
                    <?=$this->session->userdata('user_info')['designation'];?>
                  </span>
                  <!-- <span class="caret"></span> -->
                </span>
              </a>
              <div class="clearfix"></div>
            </div>
          </div>
          <ul class="nav nav-primary">
            <li class="nav-item ">
              <a href="<?=base_url("dashboard/")?>">
                <i class="fas fa-home"></i>
                <p class="">Dashboard</p>
              </a>

            </li>
            <li class="nav-section">
              <span class="sidebar-mini-icon">
                <i class="fa fa-ellipsis-h"></i>
              </span>
              <h4 class="text-section">Modules</h4>
            </li>

            <?php foreach ($module_group as $group_key => $group_value) { ?>
                    <?php $menu_id = 'module-collapse-' . $group_key; ?>
                    <li class="nav-item">
                      <a class="module-toggle" data-target="<?php echo $menu_id; ?>" href="#" aria-expanded="false" aria-haspopup="true" aria-controls="<?php echo $menu_id; ?>">
                        <i class="<?php echo $group_value['module_icon'] ?>"></i>
                        <p><?php echo $group_value['module_group'] ?></p>
                        <span class="caret"></span>
                      </a>
                      <div class="module-submenu" id="<?php echo $menu_id; ?>" role="menu" aria-label="<?php echo htmlspecialchars($group_value['module_group'], ENT_QUOTES, 'UTF-8'); ?> modules">
                        <ul class="nav nav-collapse" role="none">
                          <li>
                          <?php foreach ($module as $module_key => $module_value) { ?>
                             <?php if ($module_value['module_group'] == $group_value['module_group']) { ?>
                            <a class="module-submenu-link" href="<?=base_url(). $module_value['route']?>" role="menuitem" tabindex="-1">
                              <span class="sub-item"><?= $module_value['module'] ?></span>
                            </a>
                            <?php } ?>
                            <?php } ?>
                          </li>
                        </ul>
                      </div>
                    </li>
            <?php } ?>
          </ul>
        </div>
        <div class="logout border text-center mt-4 " >
            <a class="text-decoration-none" href="<?= html_escape(config_item('portal_url')); ?>">
              <button type="button" data-toggle="tooltip" data-placement="bottom" title="lsbizportal"
                class="btn-logout btn btn-link text-white text-decoration-none" id="btn-logout"
                data-original-title="lsbizportal"><i class="far fa-arrow-alt-circle-left px-1 text-white"></i>Back to
                Lsbiz</button></a>
          </div>
        <div class="version-container text-center ">
            <p>version 1.0.0.0</p>
        </div>
      </div>

    </div>


    <!-- End Sidebar -->

    <div class="main-panel ">
      <div class="container ">
        <!-- <div class="page-inner px-2 " style="padding-top:1px;">
          <div class="page-category"> -->

        <?php $this->load->view($main_view);?>

        <!-- </div>
        </div> -->
          <div id="modal-loading" >
 
          </div>
      </div>
    </div>

  </div>
  <div class="mobile-nav-backdrop" aria-hidden="true"></div>
  <button type="button" class="mobile-nav-fab" aria-label="Open navigation menu">
    <i class="fas fa-bars"></i>
  </button>
  <aside class="mobile-nav-drawer" aria-label="Mobile navigation">
    <div class="mobile-nav-drawer-header" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 14px 16px; border-bottom: 1px solid var(--brand-200); background: #fff;">
      <a class="mobile-nav-lsbiz-logo mobile-nav-lsbiz-header" href="<?= html_escape(config_item('portal_url')); ?>" aria-label="Back to Lsbiz">
        <img src="<?=base_url('assets/img/lsbiz.png')?>" alt="Lsbiz" />
        <i class="fas fa-external-link-alt" aria-hidden="true"></i>
      </a>
      <button type="button" class="mobile-nav-close" aria-label="Close menu">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="mobile-nav-drawer-body" style="height: calc(100% - 63px); padding-bottom: 0;">
      <a class="mobile-nav-home" href="<?=base_url('dashboard/')?>">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
      </a>
      <?php foreach ($module_group as $m_group_key => $m_group_value) { ?>
        <?php $m_menu_id = 'mobile-module-group-' . $m_group_key; ?>
        <button
          type="button"
          class="mobile-nav-group-toggle"
          data-target="<?php echo $m_menu_id; ?>"
          aria-expanded="false"
          aria-controls="<?php echo $m_menu_id; ?>"
        >
          <i class="<?php echo $m_group_value['module_icon']; ?>"></i>
          <span><?php echo $m_group_value['module_group']; ?></span>
          <span class="caret"></span>
        </button>
        <div class="mobile-nav-group-list" id="<?php echo $m_menu_id; ?>">
          <?php foreach ($module as $m_module_value) { ?>
            <?php if ($m_module_value['module_group'] == $m_group_value['module_group']) { ?>
              <a class="mobile-nav-module-link" href="<?=base_url() . $m_module_value['route']?>">
                <i class="fas fa-circle" style="font-size:8px;"></i>
                <span><?= $m_module_value['module'] ?></span>
              </a>
            <?php } ?>
          <?php } ?>
        </div>
      <?php } ?>
    </div>
  </aside>

  <div id="knetPdfPreviewOverlay" class="knet-pdf-preview-overlay d-none">
    <div class="knet-pdf-preview-modal">
      <div class="knet-pdf-preview-header">
        <div class="knet-pdf-preview-title">Document Preview</div>
        <button type="button" class="knet-pdf-preview-close" id="knetPdfPreviewClose">&#x2715;</button>
      </div>
      <div class="knet-pdf-preview-body">
        <iframe id="knetPdfPreviewIframe" name="knetPdfPreviewIframe"></iframe>
      </div>
    </div>
  </div>

  <script>
    var base_url = <?php echo '\'' . base_url() . '\''; ?>;
  </script>




  <!--   Core JS Files   -->
  <script src="<?=base_url('assets/js/core/jquery.3.2.1.min.js');?>"></script>
  <script src="<?=base_url('assets/js/core/popper.min.js');?>"></script>
  <script src="<?=base_url('assets/js/core/bootstrap.min.js');?>"></script>
  <script src="<?=base_url('assets/js/dragula.js');?>"></script>
  <script src="<?=base_url('assets/js/dragula.min.js');?>"></script>
  <script src="<?=base_url('assets/js/pubnub.7.1.2.min.js');?>"></script>
  <script src="<?= base_url('assets/js/main.js?v=' . (file_exists(FCPATH . 'assets/js/main.js') ? filemtime(FCPATH . 'assets/js/main.js') : time())); ?>"></script>
  <!-- jQuery UI -->
  <script src="<?=base_url('assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js');?>"></script>
  <script src="<?=base_url('assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js');?>"></script>
  <!-- jQuery Scrollbar -->
  <script src="<?=base_url('assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js');?>"></script>
  <!-- Moment JS -->
  <script src="<?=base_url('assets/js/plugin/moment/moment.min.js');?>"></script>
  <!-- Chart JS -->
  <script src="<?=base_url('assets/js/plugin/chart.js/chart.min.js');?>"></script>
  <script src="<?=base_url('assets/js/plugin/chart.js/chart.js');?>"></script>
  <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
  <!-- jQuery Sparkline -->
  <script src="<?=base_url('assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js');?>"></script>
  <!-- Chart Circle -->
  <script src="<?=base_url('assets/js/plugin/chart-circle/circles.min.js');?>"></script>
  <script src="<?=base_url('assets/js/plugin/chart-circle/circles.js');?>"></script>
  <script src="<?=base_url('assets/js/chartjs-plugin-datalabels.min.js');?>"></script>
  <!-- <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js"></script> -->
  <!-- Datatables -->
  <script src="https://nightly.datatables.net/js/jquery.dataTables.js"></script>
  <script src="<?=base_url('assets/js/plugin/datatables/datatables.min.js');?>"></script>
  <script src="<?=base_url('assets/js/plugin/datatables/dataTables.fixedColumns.min.js');?>"></script>
  <script src="<?=base_url('assets/js/plugin/datatables/buttons.bootstrap.min.js');?>"></script>
  <script src="<?=base_url('assets/js/plugin/datatables/dataTables.scroller.min.js');?>"></script>
  <script src="<?=base_url('assets/js/plugin/datatables/buttons.print.min.js');?>"></script>
  <script src="<?=base_url('assets/js/plugin/datatables/dataTables.buttons.min.js');?>"></script>
  <script src="<?=base_url('assets/js/plugin/datatables/buttons.html5.min.js');?>"></script>
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <!-- SheetJs -->
  <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
  <!-- Bootstrap Notify -->
  <script src="<?=base_url('assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js');?>"></script>
  <!-- Bootstrap Toggle -->
  <script src="<?=base_url('assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js');?>"></script>
  <!-- jQuery Vector Maps -->
  <!-- <script src="<?=base_url('assets/js/plugin/jqvmap/jquery.vmap.min.js');?>"></script>
  <script src="<?=base_url('assets/js/plugin/jqvmap/ma ps/jquery.vmap.world.js');?>"></script> -->
  <!-- Google Maps Plugin -->
  <!-- <script src="<?=base_url('assets/js/plugin/gmaps/gmaps.js');?>"></script> -->
  <!-- Dropzone -->
  <!-- <script src="<?=base_url('assets/js/plugin/dropzone/dropzone.min.js');?>"></script> -->
  <!-- Fullcalendar -->
  <script src="<?=base_url('assets/js/plugin/fullcalendar/fullcalendar.min.js');?>"></script>
  <!-- DateTimePicker -->
  <script src="<?=base_url('assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js');?>"></script>
  <!-- Bootstrap Tagsinput -->
  <script src="<?=base_url('assets/js/plugin/bootstrap-tagsinput/bootstrap-tagsinput.min.js');?>"></script>
  <!-- Bootstrap Wizard -->
  <script src="<?=base_url('assets/js/plugin/bootstrap-wizard/bootstrapwizard.js');?>"></script>
  <!-- jQuery Validation -->
  <script src="<?=base_url('assets/js/plugin/jquery.validate/jquery.validate.min.js');?>"></script>
  <!-- Summernote -->
  <script src="<?=base_url('assets/js/plugin/summernote/summernote-bs4.min.js');?>"></script>
  <script src="<?=base_url('assets/js/plugin/summernote/summernote-bs4.js');?>"></script>
  <!-- Select2 -->
  <script src="<?=base_url('assets/js/plugin/select2/select2.full.min.js');?>"></script>
  <!-- Sweet Alert -->
  <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
   <script src="<?=base_url('assets/js/sweetalert2@11.js');?>"></script>
  <!-- Flatpickr -->
  <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"> -->
   <link rel="stylesheet" href="<?=base_url('assets/css/flatpickr.min.css'); ?>">
  <!-- <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script> -->
      <script src="<?=base_url('assets/js/flatpickr.js');?>"></script>
  <!-- Owl Carousel -->
  <script src="<?=base_url('assets/js/plugin/owl-carousel/owl.carousel.min.js');?>"></script>
  <!-- Magnific Popup -->
  <script src="<?=base_url('assets/js/plugin/jquery.magnific-popup/jquery.magnific-popup.min.js');?>"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <!-- Atlantis JS -->
  <script src="<?=base_url('assets/js/atlantis.min.js');?>"></script>
  <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
  <!-- EXCEL JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"
    integrity="sha512-UnrKxsCMN9hFk7M56t4I4ckB4N/2HHi0w/7+B/1JsXIX3DmyBcsGpT3/BsuZMZf+6mAr0vP81syWtfynHJ69JA=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>

  <!-- GSAP -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/CustomEase.min.js"></script>

  <!-- FILE SAVER JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"
    integrity="sha512-Qlv6VSKh1gDKGoJbnyA5RMXYcvnpIqhO++MhIM2fStMcGT9i2T//tSwYFlcyoRRDcDZ+TYHpH8azBBCyhpSeqw=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>

  <script src="<?=base_url('assets/js/helpers/helper.ajax.loader.js');?>"></script>
  <script src="<?=base_url('assets/js/helpers/helper.form.validation.js');?>"></script>
  <script src="<?=base_url('assets/js/helpers/helper.datatable.js');?>"></script>
  <script src="<?=base_url('assets/js/helpers/helper.select2.js');?>"></script>
  <script src="<?=base_url('assets/js/helpers/helper.map.js');?>"></script>
  <script src="<?=base_url('assets/js/helpers/helper.transaction.viewer.js');?>"></script>
  <script src="<?=base_url('assets/js/helpers/helper.export.js');?>"></script>
  <script src="<?=base_url('assets/js/helpers/helper.swal.js');?>"></script>
  <script src="<?=base_url('assets/js/jquery-dateformat.min.js');?>"></script>
<?php if (isset($scripts)): ?>
  <?php foreach ($scripts as $script): ?>
    <?php
      $path = FCPATH . 'assets/js/modules/' . $this->module_name . '/' . $script;
      $version = file_exists($path) ? filemtime($path) : time();
    ?>
    <script src="<?= base_url('assets/js/modules/' . $this->module_name . '/' . $script . '?v=' . $version); ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($custom_scripts)): ?>
  <?php foreach ($custom_scripts as $custom_script): ?>
    <?php
      $path = FCPATH . 'assets/js/modules/' . $custom_script;
      $version = file_exists($path) ? filemtime($path) : time();
    ?>
    <script src="<?= base_url('assets/js/modules/' . $custom_script . '?v=' . $version); ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>

  <!-- Added by Carlo, April 28, 2023 -->
  <!-- PrintJS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/print-js/1.6.0/print.js"
    integrity="sha512-/fgTphwXa3lqAhN+I8gG8AvuaTErm1YxpUjbdCvwfTMyv8UZnFyId7ft5736xQ6CyQN4Nzr21lBuWWA9RTCXCw=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/print-js/1.6.0/print.css"
    integrity="sha512-tKGnmy6w6vpt8VyMNuWbQtk6D6vwU8VCxUi0kEMXmtgwW+6F70iONzukEUC3gvb+KTJTLzDKAGGWc1R7rmIgxQ=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />

</body>

</html>