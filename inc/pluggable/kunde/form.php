<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function WPsCRM_display_customer_form() {
    global $wpdb;
    $delete_nonce = wp_create_nonce( "delete_customer" );
    $update_nonce= wp_create_nonce( "update_customer" );
    $scheduler_nonce = wp_create_nonce( "update_scheduler" );
    $ID = isset($_REQUEST["ID"])?$_REQUEST["ID"]:0;
    // fallback: some installs created table as 'kunde'
    $table = WPsCRM_get_customer_table();
    $pk = WPsCRM_get_customer_pk($table);
    $ID_azienda = "1";
    $email="";
    $where = "FK_aziende=$ID_azienda";
    $current_user = wp_get_current_user();
    $agent_disabled="";
    $style_disabled="";
    $custom_tax = "";
    $riga = array(
        "agente" => "",
        "firmenname" => "",
        "name" => "",
        "nachname" => "",
        "email" => "",
        "custom_tax" => "",
        "annotazioni" => "",
        "categoria" => "",
        "provenienza" => "",
        "interessi" => "",
        "abrechnungsmodus" => "",
        "tipo_cliente" => "",
        "nation" => "",
        "cod_fis" => "",
        "p_iva" => "",
        "adresse" => "",
        "cap" => "",
        "standort" => "",
        "provinz" => "",
        "telefono1" => "",
        "fax" => "",
        "telefono2" => "",
        "luogo_nascita" => "",
        "geburtsdatum" => "",
        "sitoweb" => "",
        "note" => "",
        "skype" => "",
    );
    $cliente = "";
    // Use detected pk from earlier WPsCRM_get_customer_pk call
	is_multisite() ? $filter=get_blog_option(get_current_blog_id(), 'active_plugins' ) : $filter=get_option('active_plugins' );
	if ( in_array( 'wp-smart-crm-agents/wp-smart-crm-agents.php', apply_filters( 'active_plugins', $filter) ) ) {
		$agent_obj=new AGsCRM_agent();
		if ($agent_obj->isAgent){
			$agent_disabled="disabled='disabled'";
			$style_disabled="style='display:none'";        
		}
	}
	else {
		if ( WPsCRM_is_agent() && ! WPsCRM_agent_can() )
		{
			$agent_disabled="disabled='disabled'";
			$style_disabled="style='display:none'";
		}
	}
if ( $ID )
{
    $sql = "select * from $table where $pk=$ID";
    $riga_db = $wpdb ? $wpdb->get_row($sql, ARRAY_A) : null;
    if ($riga_db) {
        // Merge DB values with defaults to avoid undefined key warnings
        $riga = array_merge($riga, $riga_db);
    }
    $agente = isset($riga["agente"]) ? $riga["agente"] : "";
    $cliente = !empty($riga["firmenname"]) ? $riga["firmenname"] : trim((isset($riga["name"]) ? $riga["name"] : "")." ".(isset($riga["nachname"]) ? $riga["nachname"] : ""));
    $cliente = stripslashes( $cliente );
    $email = isset($riga['email']) ? $riga['email'] : "";
    $custom_tax = maybe_unserialize( isset($riga['custom_tax']) ? $riga['custom_tax'] : "" );
}

if ( ! empty ( $custom_tax ) )
	$_tax=json_encode($custom_tax);
else{
	$_tax=json_encode("");
    $custom_tax="";
    }
?>
<script>
    <?php 
    if ( in_array( 'wp-smart-crm-agents/wp-smart-crm-agents.php', apply_filters( 'active_plugins', $filter) ) ) {
    $agent_obj=new AGsCRM_agent();
    ?>
    var privileges = <?php echo json_encode($agent_obj->getCustomerPrivileges($ID, "array")) ?>;
    <?php
    } else { ?> 
    var privileges = null;
    <?php } ?>

    var customerTax = JSON.parse('<?php echo $_tax ?>');
    var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
    var $formatTime = "<?php echo WPsCRM_DATETIMEFORMAT ?>";
</script>
<!-- Tab Styling (Simple Tab Navigation) -->
<style>
    #tabstrip > ul {list-style:none;margin:0 0 10px;padding:0;display:flex;gap:2px;border-bottom:1px solid #ccc;}
    #tabstrip > ul > li {padding:8px 12px;background:#f2f2f2;border:1px solid #ccc;border-bottom:none;cursor:pointer;border-radius:4px 4px 0 0;}
    #tabstrip > ul > li.active {background:#fff;font-weight:700;border-bottom:1px solid #fff;}
    /* Hide all tab content divs by default */
    #tabstrip > div {display:none;margin-top:-1px;border:1px solid #ccc;border-top:none;padding:15px;}
    /* Show only active tab content */
    #tabstrip > div.active {display:block;}
</style>
<!-- Todo/Termin/Aktivität Modals entfernt aus dem Kundenformular (werden im Scheduler verwaltet) -->

<script type="text/javascript">
jQuery(document).ready(function ($) {
    $('._showLoader').on('click', function (e) {
        $('#mouse_loader').offset({ left: e.pageX, top: e.pageY });
    });

    // Bootstrap Modals für Rechnung und Angebot entfernt - nicht mehr nötig
    
    <?php do_action('WPsCRM_menu_tooltip') ?>

    <?php if($ID){ ?>
    $('#cd-timeline').on('click','.glyphicon-remove', function () {
        var complete=false;
        var $this=$(this).closest('.cd-timeline-block');
        var index=$this.data('index');
        $.ajax({
            url: ajaxurl,
            data: {'action': 'WPsCRM_delete_annotation',
                'id_cliente': '<?php echo $ID ?>',
                'index':index,
                'security':'<?php echo $delete_nonce; ?>'},
            type: "POST",
            success: function (response) {
                noty({
                    text: "<?php _e('Anmerkung wurde gelöscht','cpsmartcrm')?>",
                    layout: 'center',
                    type: 'success',
                    template: '<div class="noty_message"><span class="noty_text"></span></div>',
                    timeout: 1000
                });
                complete=true;
                $("*[data-index=" + index + "]").fadeOut(200);
            }
        })
    })
    <?php } ?>

    // Timeline-Animation
    var timelineBlocks = $('.cd-timeline-block'),
        offset = 0.8;
    hideBlocks(timelineBlocks, offset);
    $(window).on('scroll', function () {
        (!window.requestAnimationFrame)
            ? setTimeout(function () { showBlocks(timelineBlocks, offset); }, 100)
            : window.requestAnimationFrame(function () { showBlocks(timelineBlocks, offset); });
    });
    function hideBlocks(blocks, offset) {
        blocks.each(function () {
            ($(this).offset().top > $(window).scrollTop() + $(window).height() * offset) && $(this).find('.cd-timeline-img, .cd-timeline-content').addClass('is-hidden');
        });
    }
    function showBlocks(blocks, offset) {
        blocks.each(function () {
            ($(this).offset().top <= $(window).scrollTop() + $(window).height() * offset && $(this).find('.cd-timeline-img').hasClass('is-hidden')) && $(this).find('.cd-timeline-img, .cd-timeline-content').removeClass('is-hidden').addClass('bounce-in');
        });
    }

    // update activity aus Modal
    $(document).on('click', '#save_activity_from_modal', function () {
        var id = $(this).data('id');
        $('.modal_loader').show();
        $.ajax({
            url: ajaxurl,
            method:'POST',
            data: {
                'action': 'WPsCRM_scheduler_update',
                'ID': id,
                'fatto': $('input[type="radio"][name="fatto"]:checked').val(),
                'esito': $('#esito').val(),
                'security':'<?php echo $scheduler_nonce; ?>'
            },
            success: function (response) {
                $('#grid').DataTable().ajax.reload();
                setTimeout(function () {
                    $('.modal_loader').fadeOut('fast');
                }, 300);
                setTimeout(function () {
                    $('._modal').fadeOut('fast');
                }, 500);
            },
            error: function (errorThrown) {
                // AJAX Fehler
            }
        })
    });

    // jQuery UI Datepicker
    $("#einstiegsdatum").datepicker({
        dateFormat: "dd.mm.yy",
        changeMonth: true,
        changeYear: true,
        yearRange: "1900:+10"
    });
    
    // Setze heutiges Datum für neue Kunden (wenn Feld leer ist)
    <?php if (!$ID): ?>
    if (!$("#einstiegsdatum").val()) {
        $("#einstiegsdatum").datepicker('setDate', new Date());
    }
    <?php endif; ?>
    
    $("#geburtsdatum").datepicker({
        dateFormat: "dd.mm.yy",
        changeMonth: true,
        changeYear: true,
        yearRange: "1900:+10"
    });

    // Einfacher Editor für Aktivitäten-Notizen
    $(document).on('click', '#attivita_editor_toolbar button', function(e) {
        e.preventDefault();
        var command = $(this).data('command');
        document.execCommand(command, false, null);
        $('#attivita_editor').focus();
    });

    // Beim Öffnen des Modals: Datum setzen und Editor leeren
    $(document).on('click', '.btn-activity, .btn-attivita', function() {
        var id = $(this).data('id');
        $('#attivita_fk_cliente').val(id);
        
        // Setze heutiges Datum
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var yyyy = today.getFullYear();
        $('#attivita_datum').val(dd + '.' + mm + '.' + yyyy);
        
        // Leere Editor
        $('#attivita_editor').html('');
    });

    // Speichern-Button im Modal
    $(document).on('click', '#btn_save_attivita', function() {
        if (typeof saveAttivita === 'function') {
            saveAttivita();
        }
    });

    // Abbrechen-Button im Modal
    $(document).on('click', '#btn_cancel_attivita', function() {
        if (typeof attivitaModal !== 'undefined' && attivitaModal) {
            attivitaModal.close();
        } else {
            $('.pscrm-modal-close').click();
        }
    });

    // AGENT (Select2)
    if ($("#selectAgent").length) {
        $.ajax({
            url: ajaxurl,
            data: {
                'action': 'WPsCRM_get_CRM_users_customer'
            },
            success: function (result) {
                var data = [];
                if (Array.isArray(result)) {
                    data = result.map(function(user) {
                        return { id: user.ID, text: user.display_name };
                    });
                }
                $("#selectAgent").select2({
                    data: data,
                    placeholder: "<?php _e('Select Agent...','cpsmartcrm') ?>",
                    width: '54%'
                });
                var agente = '<?php if(isset($agente)) echo $agente?>';
                if (agente > 0) {
                    $("#selectAgent").val(agente).trigger('change');
                }
            },
            error: function (errorThrown) {
                // AJAX Fehler
            }
        });
    }

    // LAND (Select2)
    $('#nation').select2({
        placeholder: "<?php _e('Land auswählen','cpsmartcrm') ?>...",
        width: '100%'
    });
    // Felder aktivieren/deaktivieren je nach Land
    var country = $('#nation').val();
    if (country != "0") {
        $('._toCheck').attr({ 'readonly': false, 'title': '' });
    } else {
        $('._toCheck').attr({ 'readonly': 'readonly', 'title': '<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...', 'alt': '<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...' });
    }
    $('#nation').on('change', function () {
        if ($(this).val() != "0") {
            $('._toCheck').attr({ 'readonly': false, 'title': '' });
        } else {
            $('._toCheck').attr({ 'readonly': 'readonly', 'title': '<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...', 'alt': '<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...' });
        }
    });

    // KATEGORIE (Select2 als Mehrfachauswahl)
    <?php
        $cats = WPsCRM_get_customer_field_values('categoria');
        $catDbValue = isset($riga) && !empty($riga["categoria"]) ? $riga["categoria"] : '';
        echo "var cats = [];";
        if( ! empty($cats) ){
            echo "cats = [";
            foreach($cats as $cat)
                echo '{id:"'.$cat->term_id.'",text:"'.$cat->name.'"},';
            echo "];";
        }
    ?>
    function initSelectCategory() {
        if (typeof jQuery.fn.select2 === 'undefined') {
            setTimeout(initSelectCategory, 100);
            return;
        }
        jQuery('#customerCategory').select2({
            data: cats,
            placeholder: "<?php _e('Wählen','cpsmartcrm')?>",
            width: '100%',
            multiple: true,
            tags: true,
            tokenSeparators: [',']
        });
        <?php if (!empty($catDbValue)): ?>
            var catDbValue = <?php echo json_encode(explode(',', $catDbValue)); ?>;
            jQuery('#customerCategory').val(catDbValue).trigger('change');
            // Fallback: If some IDs don't exist, add them as tags
            setTimeout(function() {
                var selectedData = jQuery('#customerCategory').select2('data');
                var selectedIds = selectedData.map(function(item) { return item.id; });
                catDbValue.forEach(function(dbId) {
                    if (!selectedIds.includes(dbId)) {
                        var termName = 'ID:' + dbId;
                        cats.forEach(function(cat) { if (cat.id == dbId) termName = cat.text; });
                        jQuery('#customerCategory').append(new Option(termName, dbId, false, false)).val(catDbValue).trigger('change');
                    }
                });
            }, 100);
        <?php endif; ?>
    }

    // PROVENIENZ (Select2 als Mehrfachauswahl)
    <?php
        $provs = WPsCRM_get_customer_field_values('provenienza');
        $provDbValue = isset($riga) && !empty($riga["provenienza"]) ? $riga["provenienza"] : '';
        echo "var provs = [];";
        if( ! empty($provs) ){
            echo "provs = [";
            foreach($provs as $prov)
                echo '{id:"'.$prov->term_id.'",text:"'.$prov->name.'"},';
            echo "];";
        }
    ?>
    function initSelectProvenienz() {
        if (typeof jQuery.fn.select2 === 'undefined') {
            setTimeout(initSelectProvenienz, 100);
            return;
        }
        jQuery('#customerComesfrom').select2({
            data: provs,
            placeholder: "<?php _e('Wählen','cpsmartcrm')?>",
            width: '100%',
            multiple: true,
            tags: true,
            tokenSeparators: [',']
        });
        <?php if (!empty($provDbValue)): ?>
            var provDbValue = <?php echo json_encode(explode(',', $provDbValue)); ?>;
            jQuery('#customerComesfrom').val(provDbValue).trigger('change');
            // Fallback: If some IDs don't exist, add them as tags
            setTimeout(function() {
                var selectedData = jQuery('#customerComesfrom').select2('data');
                var selectedIds = selectedData.map(function(item) { return item.id; });
                provDbValue.forEach(function(dbId) {
                    if (!selectedIds.includes(dbId)) {
                        var termName = 'ID:' + dbId;
                        provs.forEach(function(prov) { if (prov.id == dbId) termName = prov.text; });
                        jQuery('#customerComesfrom').append(new Option(termName, dbId, false, false)).val(provDbValue).trigger('change');
                    }
                });
            }, 100);
        <?php endif; ?>
    }

    // INTERESSEN (Select2 als Mehrfachauswahl)
    <?php
        $ints = WPsCRM_get_customer_field_values('interessi');
        $intDbValue = isset($riga) && !empty($riga["interessi"]) ? $riga["interessi"] : '';
        echo "var ints = [];";
        if( ! empty($ints) ){
            echo "ints = [";
            foreach($ints as $int)
                echo '{id:"'.$int->term_id.'",text:"'.$int->name.'"},';
            echo "];";
        }
    ?>
    function initSelectInterests() {
        if (typeof jQuery.fn.select2 === 'undefined') {
            setTimeout(initSelectInterests, 100);
            return;
        }
        jQuery('#customerInterests').select2({
            data: ints,
            placeholder: "<?php _e('Wählen','cpsmartcrm')?>",
            width: '100%',
            multiple: true,
            tags: true,
            tokenSeparators: [',']
        });
        <?php if (!empty($intDbValue)): ?>
            var intDbValue = <?php echo json_encode(explode(',', $intDbValue)); ?>;
            jQuery('#customerInterests').val(intDbValue).trigger('change');
            // Fallback: If some IDs don't exist, add them as tags
            setTimeout(function() {
                var selectedData = jQuery('#customerInterests').select2('data');
                var selectedIds = selectedData.map(function(item) { return item.id; });
                intDbValue.forEach(function(dbId) {
                    if (!selectedIds.includes(dbId)) {
                        var termName = 'ID:' + dbId;
                        ints.forEach(function(int) { if (int.id == dbId) termName = int.text; });
                        jQuery('#customerInterests').append(new Option(termName, dbId, false, false)).val(intDbValue).trigger('change');
                    }
                });
            }, 100);
        <?php endif; ?>
    }

    // Initialize all SELECT2 fields when page loads
    initSelectCategory();
    initSelectProvenienz();
    initSelectInterests();

    // SELECT2 change event handlers - speichere Werte wenn sich Selection ändert
    $('#customerCategory').on('change', function(e) {
        var s2Data = $(this).select2('data');
        // Extrahiere alle IDs aus den SELECT2-Daten
        var ids = s2Data.map(function(item) { return item.id; });
        var idString = ids.join(',');
        // Speichere in das value-Attribut
        $(this).attr('value', idString);
    });
    
    $('#customerComesfrom').on('change', function(e) {
        var s2Data = $(this).select2('data');
        var ids = s2Data.map(function(item) { return item.id; });
        var idString = ids.join(',');
        $(this).attr('value', idString);
    });
    
    $('#customerInterests').on('change', function(e) {
        var s2Data = $(this).select2('data');
        var ids = s2Data.map(function(item) { return item.id; });
        var idString = ids.join(',');
        $(this).attr('value', idString);
    });

    // Parsley für Validierung
    $('#form_insert').parsley({
        errorsWrapper: '<div class="parsley-errors-list"></div>',
        errorTemplate: '<div></div>',
        trigger: 'change'
    });

    // Eigene Parsley-Regeln
    window.Parsley.addValidator('country', {
        validateString: function(value) {
            return value !== "0" && value !== null && value !== "";
        },
        messages: {
            de: "Du solltest das Kundenland auswählen"
        }
    });
    window.Parsley.addValidator('fiscalcode', {
        validateString: function(value) {
            var country = $('#nation').val();
            if ($('#abrechnungsmodus_1').is(':checked')) {
                if (value === "" && $('#p_iva').val() === "") return false;
                if (country === "DE" && value.length !== 16) return false;
            }
            return true;
        },
        messages: {
            de: "Du solltest die GÜLTIGE Steuernummer oder Umsatzsteuer-Identifikationsnummer des Kunden eingeben"
        }
    });

    // Parsley-Felder zuweisen
    $('#nation').attr('data-parsley-country', '');
    $('#cod_fis').attr('data-parsley-fiscalcode', '');

    // Speichern-Button
    $(document).on('click', '.saveForm', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $form = $('#form_insert');
        if ($form.parsley().validate()) {
            // Get SELECT2 values directly from the SELECT2 API
            var catVals = $('#customerCategory').val() || [];
            var intVals = $('#customerInterests').val() || [];
            var provVals = $('#customerComesfrom').val() || [];
            
            // Serialize form + manually add SELECT2 values
            var formData = $form.serialize() + 
                '&customerCategory=' + encodeURIComponent(Array.isArray(catVals) ? catVals.join(',') : catVals) +
                '&customerInterests=' + encodeURIComponent(Array.isArray(intVals) ? intVals.join(',') : intVals) +
                '&customerComesfrom=' + encodeURIComponent(Array.isArray(provVals) ? provVals.join(',') : provVals);
            
            showMouseLoader();
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'WPsCRM_save_client',
                    fields: formData,
                    security: '<?php echo $update_nonce; ?>'
                },
                type: "POST",
                success: function (response) {
                    hideMouseLoader();
                    if (response.indexOf('OK') !== -1) {
                        var tmp = response.split("~");
                        var id_cli = tmp[1];
                        noty({
                            text: "<?php _e('Der Kunde wurde gespeichert','cpsmartcrm')?>",
                            layout: 'center',
                            type: 'success',
                            template: '<div class="noty_message"><span class="noty_text"></span></div>',
                            timeout: 1000
                        });
                        $("#ID").val(id_cli);
                        <?php if (! $ID) { ?>
                        setTimeout(function () {
                            location.href = "<?php echo admin_url('admin.php?page=smart-crm&p=kunde/form.php&ID=')?>" + id_cli;
                        }, 1000)
                        <?php } ?>
                    } else {
                        noty({
                            text: "<?php _e('Etwas war falsch','cpsmartcrm')?>" + ": " + response,
                            layout: 'center',
                            type: 'error',
                            template: '<div class="noty_message"><span class="noty_text"></span></div>',
                            closeWith: ['button']
                        });
                    }
                }
            });
        }
    });

    // Reset-Button
    $('.resetForm').on('click', function(e) {
        e.preventDefault();
        location.href = "<?php echo admin_url('admin.php?page=smart-crm&p=kunde/list.php')?>";
    });

    // Löschen-Button
    $('.deleteForm').on('click', function(e) {
        e.preventDefault();
        if (confirm("<?php _e('Löschen bestätigen? Es ist weiterhin möglich, den gelöschten Kunden wiederherzustellen ','cpsmartcrm')?>")) {
            location.href = "<?php echo admin_url('admin.php?page=smart-crm&p=kunde/delete.php&ID='.$ID)?>&security=<?php echo $delete_nonce?>";
        }
    });

    // Simple Tab Navigation (replacement for Kendo UI TabStrip)
    var $ts = $('#tabstrip');
    if ($ts.length) {
        var $tabs = $ts.children('ul').children('li');
        var $panes = $ts.children('div');
        function activate(i){
            $tabs.removeClass('active');
            $panes.removeClass('active').css('display', 'none');
            $tabs.eq(i).addClass('active');
            $panes.eq(i).addClass('active').css('display', 'block');
        }
        $tabs.each(function(i){
            $(this).on('click', function(e){ 
                e.preventDefault(); 
                activate(i);
                // Setze Hash für aktuellen Tab
                window.location.hash = 'tab-' + i;
            });
        });
        
        // Prüfe URL-Hash und aktiviere entsprechenden Tab
        var hash = window.location.hash;
        var initialTab = 0;
        if (hash === '#tab-0') initialTab = 0;
        else if (hash === '#tab-1') initialTab = 1;
        else if (hash === '#tab-2') initialTab = 2;
        else if (hash === '#tab-3') initialTab = 3;
        
        activate(initialTab);
        
        // Grid Initialisierung - nur bei vorhandenem Kunden
        <?php if ($ID): ?>
        var gridContactsInitialized = false;
        var gridQuotesInitialized = false;
        var gridSchedulerInitialized = false;
        
        // Tab-Wechsel Event - initialisiere Grids lazy
        $tabs.each(function(i){
            var $tab = $(this);
            $tab.on('click', function(){
                // TAB 2 - Kontakte Grid
                if (i === 1 && !gridContactsInitialized) {
                    gridContactsInitialized = true;
                    initScheduleGrid();
                }
                // TAB 3 - Angebote Grid
                if (i === 2 && !gridQuotesInitialized) {
                    gridQuotesInitialized = true;
                    initQuotesGrid();
                }

            });
        });
        
        // Lade Grid für initial aktivierten Tab
        if (initialTab === 1 && !gridContactsInitialized) {
            gridContactsInitialized = true;
            initScheduleGrid();
        } else if (initialTab === 2 && !gridQuotesInitialized) {
            gridQuotesInitialized = true;
            initQuotesGrid();
        }
        
        // Kontakte Grid initialisieren
        function initScheduleGrid() {
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'WPsCRM_get_client_schedule',
                    client_id: <?php echo $ID; ?>
                },
                success: function(response) {
                    console.log('Schedule response:', response);
                    var schedules = response.schedules || [];
                    var $grid = jQuery('#grid_contacts');
                    if ($grid.length) {
                        var html = '';
                        if (schedules.length > 0) {
                            html += '<table class="table table-striped table-hover">';
                            html += '<thead><tr>';
                            html += '<th><?php _e("Datum","cpsmartcrm"); ?></th>';
                            html += '<th><?php _e("Titel","cpsmartcrm"); ?></th>';
                            html += '<th><?php _e("Typ","cpsmartcrm"); ?></th>';
                            html += '<th><?php _e("Priorität","cpsmartcrm"); ?></th>';
                            html += '<th><?php _e("Status","cpsmartcrm"); ?></th>';
                            html += '<th><?php _e("Aktionen","cpsmartcrm"); ?></th>';
                            html += '</tr></thead><tbody>';
                            schedules.forEach(function(item){
                                var type_text = item.tipo_agenda == 1 ? '<?php _e("Todo","cpsmartcrm"); ?>' : '<?php _e("Termin","cpsmartcrm"); ?>';
                                var status_text = item.fatto == 1 ? '<?php _e("Offen","cpsmartcrm"); ?>' : '<?php _e("Abgeschlossen","cpsmartcrm"); ?>';
                                var status_badge = item.fatto == 1 ? 'badge badge-warning' : 'badge badge-success';
                                html += '<tr>';
                                html += '<td>' + (item.start_date || '') + '</td>';
                                html += '<td>' + (item.oggetto || '') + '</td>';
                                html += '<td>' + type_text + '</td>';
                                html += '<td>' + (item.priorita || '') + '</td>';
                                html += '<td><span class="' + status_badge + '">' + status_text + '</span></td>';
                                html += '<td>';
                                html += '<a href="#" class="btn btn-sm btn-info schedule-edit" data-id="' + item.id + '" title="<?php _e("Bearbeiten","cpsmartcrm"); ?>"><i class="glyphicon glyphicon-pencil"></i></a> ';
                                html += '<a href="#" class="btn btn-sm btn-danger schedule-delete" data-id="' + item.id + '" title="<?php _e("Löschen","cpsmartcrm"); ?>"><i class="glyphicon glyphicon-trash"></i></a>';
                                html += '</td>';
                                html += '</tr>';
                            });
                            html += '</tbody></table>';
                        } else {
                            html = '<div class="alert alert-info"><?php _e("Keine Termine oder Todos vorhanden","cpsmartcrm"); ?></div>';
                        }
                        $grid.html(html);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Schedule Grid Fehler:', error, xhr.responseText);
                    jQuery('#grid_contacts').html('<div class="alert alert-warning"><?php _e("Fehler beim Laden der Termine","cpsmartcrm"); ?></div>');
                }
            });
        }
        
        // Angebote Grid initialisieren
        function initQuotesGrid() {
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'WPsCRM_get_documents_for_customer',
                    id_cliente: <?php echo $ID; ?>,
                    security: '<?php echo wp_create_nonce("mailToCustomer"); ?>'
                },
                success: function(response) {
                    console.log('Documents response:', response);
                    var documents = response.documents || [];
                    
                    // Filter Angebote (tipo = 1)
                    var quotes = documents.filter(function(doc){ return doc.tipo == 1; });
                    var $gridQuotes = jQuery('#grid_quotes');
                    if ($gridQuotes.length) {
                        var html = '';
                        if (quotes.length > 0) {
                            // Button oberhalb der Tabelle
                            html += '<div style="margin-bottom:15px;">';
                            html += '<button type="button" class="btn btn-success btn_new_quote" title="<?php _e("Neues Angebot für diesen Kunden erstellen","cpsmartcrm"); ?>">';
                            html += '<i class="glyphicon glyphicon-send"></i> ';
                            html += '<?php _e("Neues Angebot","cpsmartcrm"); ?>';
                            html += '</button>';
                            html += '</div>';
                            
                            html += '<table class="table table-striped table-hover">';
                            html += '<thead><tr>';
                            html += '<th><?php _e("Nr.","cpsmartcrm"); ?></th>';
                            html += '<th><?php _e("Datum","cpsmartcrm"); ?></th>';
                            html += '<th><?php _e("Betrag","cpsmartcrm"); ?></th>';
                            html += '<th><?php _e("Aktionen","cpsmartcrm"); ?></th>';
                            html += '</tr></thead><tbody>';
                            quotes.forEach(function(quote){
                                var editUrl = '<?php echo admin_url("admin.php?page=smart-crm&p=dokumente/form_quotation.php&ID="); ?>' + quote.id;
                                var printUrl = '<?php echo admin_url("admin.php?page=smart-crm&p=dokumente/document_print.php&id_invoice="); ?>' + quote.id;
                                html += '<tr>';
                                html += '<td>' + (quote.progressivo || quote.id) + '</td>';
                                html += '<td>' + (quote.culture_einstiegsdatum || quote.einstiegsdatum || '') + '</td>';
                                html += '<td>' + (quote.totale_doc || '0.00') + ' <?php echo WPsCRM_DEFAULT_CURRENCY_SYMBOL; ?></td>';
                                html += '<td>';
                                html += '<a href="' + editUrl + '" class="btn btn-sm btn-primary" title="<?php _e("Bearbeiten","cpsmartcrm"); ?>"><i class="glyphicon glyphicon-pencil"></i></a> ';
                                html += '<a href="' + printUrl + '" target="_blank" class="btn btn-sm btn-info" title="<?php _e("Drucken","cpsmartcrm"); ?>"><i class="glyphicon glyphicon-print"></i></a>';
                                html += '</td>';
                                html += '</tr>';
                            });
                            html += '</tbody></table>';
                        } else {
                            // Keine Angebote: Zeige Text + Button
                            html += '<div class="alert alert-info" style="margin-bottom:20px;">';
                            html += '<p style="margin-bottom:15px;font-size:1.1em;"><?php _e("Dieser Kunde hat noch keine Angebote erhalten. Erstelle ein","cpsmartcrm"); ?></p>';
                            html += '<button type="button" class="btn btn-success btn-lg btn_new_quote" title="<?php _e("Neues Angebot für diesen Kunden erstellen","cpsmartcrm"); ?>">';
                            html += '<i class="glyphicon glyphicon-send"></i> ';
                            html += '<?php _e("Neues Angebot","cpsmartcrm"); ?>';
                            html += '</button>';
                            html += '</div>';
                        }
                        $gridQuotes.html(html);
                        
                        // Button-Handler neu binden (da dynamisch erstellt)
                        $gridQuotes.find('.btn_new_quote').on('click', function(e) {
                            e.preventDefault();
                            var quoteUrl = '<?php echo admin_url("admin.php?page=smart-crm&p=dokumente/form_quotation.php&cliente=".$ID); ?>';
                            window.location.href = quoteUrl;
                        });
                    }
                    
                    // Filter Rechnungen (tipo = 2)
                    var invoices = documents.filter(function(doc){ return doc.tipo == 2; });
                    var $gridInvoices = jQuery('#grid_invoices');
                    if ($gridInvoices.length) {
                        var htmlInv = '';
                        if (invoices.length > 0) {
                            // Button oberhalb der Tabelle
                            htmlInv += '<div style="margin-bottom:15px;">';
                            htmlInv += '<button type="button" class="btn btn-success btn_new_invoice" title="<?php _e("Neue Rechnung für diesen Kunden erstellen","cpsmartcrm"); ?>">';
                            htmlInv += '<i class="glyphicon glyphicon-file"></i> ';
                            htmlInv += '<?php _e("Neue Rechnung","cpsmartcrm"); ?>';
                            htmlInv += '</button>';
                            htmlInv += '</div>';
                            
                            htmlInv += '<table class="table table-striped table-hover">';
                            htmlInv += '<thead><tr>';
                            htmlInv += '<th><?php _e("Nr.","cpsmartcrm"); ?></th>';
                            htmlInv += '<th><?php _e("Datum","cpsmartcrm"); ?></th>';
                            htmlInv += '<th><?php _e("Betrag","cpsmartcrm"); ?></th>';
                            htmlInv += '<th><?php _e("Status","cpsmartcrm"); ?></th>';
                            htmlInv += '<th><?php _e("Aktionen","cpsmartcrm"); ?></th>';
                            htmlInv += '</tr></thead><tbody>';
                            invoices.forEach(function(invoice){
                                var editUrl = '<?php echo admin_url("admin.php?page=smart-crm&p=dokumente/form_invoice.php&ID="); ?>' + invoice.id;
                                var printUrl = '<?php echo admin_url("admin.php?page=smart-crm&p=dokumente/document_print.php&id_invoice="); ?>' + invoice.id;
                                var statusBadge = invoice.pagato == 'Yes' ? '<span class="label label-success"><?php _e("Bezahlt","cpsmartcrm"); ?></span>' : '<span class="label label-warning"><?php _e("Offen","cpsmartcrm"); ?></span>';
                                htmlInv += '<tr>';
                                htmlInv += '<td>' + (invoice.progressivo || invoice.id) + '</td>';
                                htmlInv += '<td>' + (invoice.culture_einstiegsdatum || invoice.einstiegsdatum || '') + '</td>';
                                htmlInv += '<td>' + (invoice.totale_doc || '0.00') + ' <?php echo WPsCRM_DEFAULT_CURRENCY_SYMBOL; ?></td>';
                                htmlInv += '<td>' + statusBadge + '</td>';
                                htmlInv += '<td>';
                                htmlInv += '<a href="' + editUrl + '" class="btn btn-sm btn-primary" title="<?php _e("Bearbeiten","cpsmartcrm"); ?>"><i class="glyphicon glyphicon-pencil"></i></a> ';
                                htmlInv += '<a href="' + printUrl + '" target="_blank" class="btn btn-sm btn-info" title="<?php _e("Drucken","cpsmartcrm"); ?>"><i class="glyphicon glyphicon-print"></i></a>';
                                htmlInv += '</td>';
                                htmlInv += '</tr>';
                            });
                            htmlInv += '</tbody></table>';
                        } else {
                            // Keine Rechnungen: Zeige Text + Button
                            htmlInv += '<div class="alert alert-info" style="margin-bottom:20px;">';
                            htmlInv += '<p style="margin-bottom:15px;font-size:1.1em;"><?php _e("Dieser Kunde hat noch keine Rechnungen. Erstelle eine","cpsmartcrm"); ?></p>';
                            htmlInv += '<button type="button" class="btn btn-success btn-lg btn_new_invoice" title="<?php _e("Neue Rechnung für diesen Kunden erstellen","cpsmartcrm"); ?>">';
                            htmlInv += '<i class="glyphicon glyphicon-file"></i> ';
                            htmlInv += '<?php _e("Neue Rechnung","cpsmartcrm"); ?>';
                            htmlInv += '</button>';
                            htmlInv += '</div>';
                        }
                        $gridInvoices.html(htmlInv);
                        
                        // Button-Handler neu binden (da dynamisch erstellt)
                        $gridInvoices.find('.btn_new_invoice').on('click', function(e) {
                            e.preventDefault();
                            var invoiceUrl = '<?php echo admin_url("admin.php?page=smart-crm&p=dokumente/form_invoice.php&cliente=".$ID); ?>';
                            window.location.href = invoiceUrl;
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Documents Fehler:', error, xhr.responseText);
                    jQuery('#grid_quotes').html('<div class="alert alert-warning"><?php _e("Fehler beim Laden der Dokumente","cpsmartcrm"); ?></div>');
                    jQuery('#grid_invoices').html('');
                }
            });
        }
        <?php endif; ?>
    }
    
    // TODO, APPOINTMENT und ACTIVITY Event-Handler
    <?php 
    // Event-Handler für Todo, Termin und Aktivität Buttons
    if ($ID) {
        include(WPsCRM_DIR."/inc/crm/kunde/script_todo.php");
        include(WPsCRM_DIR."/inc/crm/kunde/script_appuntamento.php");
        include(WPsCRM_DIR."/inc/crm/kunde/script_attivita.php");
    }
    ?>
});
</script>
<form name="form_insert" method="post" id="form_insert">
<input type="hidden" name="ID" id="ID" value="<?php echo $ID?>">
    
    <h3><?php if ($ID) { ?> <?php _e('Kunde','cpsmartcrm')?>: <?php echo "<span class=\"header_customer\">".stripslashes($cliente)."</span>";
			  } else{
        ?> <?php _e('Neukunde','cpsmartcrm')?> <?php } ?>
    </h3>

    <div id="tabstrip" style="margin-top:14px">
        <ul>
            <li id="tab1"><?php _e('Stammdaten','cpsmartcrm')?></li>
            <?php if ($ID){ ?>
            <li id="tab2"><?php _e('Kontakte','cpsmartcrm')?></li>
            <li id="tab3"><?php _e('Dokumente','cpsmartcrm')?></li>
            <li id="tab4"><?php _e('Zusammenfassung','cpsmartcrm')?></li>
            <?php 
                do_action('WPsCRM_add_tabs_to_customer_form');
            } ?>
        </ul>
        <!-- TAB 1 -->
        <div>
            <div id="d_anagrafica" style="position:relative">
                <div class="row form-group">
					<label class="col-sm-1 control-label">
						<?php _e('Datum','cpsmartcrm')?>
					</label>
					<div class="col-sm-2">
						<input type="text" id="einstiegsdatum" name="einstiegsdatum" value="<?php if(isset($riga)) echo WPsCRM_inverti_data($riga["einstiegsdatum"])?>" />
					</div>
					<?php do_action('WPsCRM_display_anagrafiche_in_form') ?>

                </div>
				<div class="row form-group">
					<label class="col-sm-1 control-label">
						<?php _e('Rechnungspflichtig?','cpsmartcrm')?>
					</label>
					<div class="col-sm-4">
                        <span style="margin-right:20px"><input type="radio" name="abrechnungsmodus" id="abrechnungsmodus_1" value="1" <?php if (isset($riga) && $riga["abrechnungsmodus"]==1) echo "checked"?> /><?php _e('Ja','cpsmartcrm')?></span>
                        <span><input type="radio" name="abrechnungsmodus" id="abrechnungsmodus_2" value="0" <?php if ((isset($riga) && $riga["abrechnungsmodus"]==0) || !isset($riga)) echo "checked"?> /><?php _e('Nein','cpsmartcrm')?></span>
					</div>

					<label class="col-sm-1 control-label">
						<?php _e('Typ','cpsmartcrm')?>
					</label>
					<div class="col-sm-4">
						<input type="radio" name="tipo_cliente" value="1" <?php if (isset($riga) && $riga["tipo_cliente"]==1) echo "checked"?> /><?php _e('Privat','cpsmartcrm')?>
						<input type="radio" name="tipo_cliente" value="2" <?php if (isset($riga) && $riga["tipo_cliente"]==2) echo "checked"?> /><?php _e('Business','cpsmartcrm')?>
					</div>
				</div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Land','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <select data-nation="<?php if(isset($riga)) echo $riga["nation"]?>" id="nation" name="nation" size="20" maxlength='50'><?php if(isset($riga['nation'])) echo stripslashes( WPsCRM_get_countries($riga["nation"]) ); else echo stripslashes( WPsCRM_get_countries('DE'))?></select>
                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Firmenname','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="firmenname" maxlength='250' value="<?php if(isset($riga)) echo stripslashes($riga["firmenname"])?>" class="form-control">
                    </div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Steuer-Code','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="cod_fis" id="cod_fis" value="<?php if(isset($riga)) echo $riga["cod_fis"]?>" class="form-control _toCheck"  readonly title="<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...">
                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Umsatzsteuer-Identifikationsnummer','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="p_iva" id="p_iva" value="<?php if(isset($riga)) echo $riga["p_iva"]?>" class="form-control _toCheck"  readonly title="<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...">
                    </div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Vorname','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="name" value="<?php if(isset($riga)) echo stripslashes($riga["name"])?>" class="form-control">
                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Nachname','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="nachname" value="<?php if(isset($riga)) echo stripslashes($riga["nachname"])?>" class="form-control">
                    </div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Addresse','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="adresse" size="50" maxlength='50' value="<?php if(isset($riga)) echo stripslashes($riga["adresse"])?>" class="form-control">
                    </div>
                    <label class="col-sm-1 control-label"><?php _e('PLZ','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="cap" size="10" maxlength='10' value="<?php if(isset($riga)) echo $riga["cap"]?>" class="form-control">
                    </div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Stadt','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="standort" size="50" maxlength='55' value="<?php if(isset($riga)) echo stripslashes($riga["standort"])?>" class="form-control">
                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Staat/Prov.','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="provinz" size="5" maxlength='5' value="<?php if(isset($riga)) echo $riga["provinz"]?>" class="form-control">
                    </div>
                </div>
                <div class="row form-group">

                    <label class="col-sm-1 control-label"><?php _e('Telefon','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="telefono1" size="20" maxlength='50' value="<?php if(isset($riga)) echo $riga["telefono1"]?>" class="form-control">
                    </div>
					<label class="col-sm-1 control-label">
						<?php _e('Fax','cpsmartcrm')?>
					</label>
					<div class="col-sm-4">
						<input type="text" name="fax" size="20" maxlength='50' value="<?php if(isset($riga)) echo $riga["fax"]?>" class="form-control" />
					</div>
                   
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Mobil','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="telefono2" size="20" maxlength='50' value="<?php if(isset($riga)) echo $riga["telefono2"]?>" class="form-control">
                    </div>
					<label class="col-sm-1 control-label">
						<?php _e('Email','cpsmartcrm')?>
					</label>
					<div class="col-sm-4">
						<input type="text" name="email" size="20" maxlength='50' value="<?php if(isset($riga)) echo $riga["email"]?>" class="form-control" />
					</div>
                    
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Geburtsort','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="luogo_nascita" size="20" maxlength='50' value="<?php if(isset($riga)) echo stripslashes($riga["luogo_nascita"] )?>" class="form-control">
                    </div>
					<label class="col-sm-1 control-label">
						<?php _e('Skype','cpsmartcrm')?>
					</label>
					<div class="col-sm-4">
						<input type="text" name="skype" size="20" maxlength='100' value="<?php if(isset($riga)) echo $riga["skype"]?>" class="form-control" />
					</div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Geburtsdatum','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" id="geburtsdatum" name="geburtsdatum" value="<?php if(isset($riga)) echo WPsCRM_inverti_data($riga["geburtsdatum"])?>">
                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Kategorie','cpsmartcrm')?></label>
                    <div class="col-sm-4">
						<select id="customerCategory" name="customerCategory" multiple="multiple" style="width:100%;"></select>
						<?php
						$cats = WPsCRM_get_customer_field_values('categoria');
						if (empty($cats)): ?>
							<div class="alert alert-warning">
								<?php _e('Keine Kategorien; Erstelle Kategorien in den CRM-Einstellungen ->Seite „Kundeneinstellungen“.','cpsmartcrm') ?>
							</div>
						<?php endif; ?>
						<script>
							<?php
							echo "var cats = [];";
							if( ! empty($cats) ){
								echo "cats = [";
								foreach($cats as $cat)
									echo '{id:"'.$cat->term_id.'",text:"'.$cat->name.'"},';
								echo "];";
							}
							?>
							<!-- customerCategory select2 initialized in main script block above -->
						</script>
					</div>
                </div>
                <div class="row form-group">
					<label class="col-sm-1 control-label"><?php _e('Webseite','cpsmartcrm')?></label>
					<div class="col-sm-4">
						<input type="text" name="sitoweb" size="20" maxlength='50' value="<?php if(isset($riga)) echo $riga["sitoweb"]?>" class="form-control">
					</div>
					<label class="col-sm-1 control-label"><?php _e('Interessen','cpsmartcrm')?></label>
					<div class="col-sm-4">
						<select id="customerInterests" name="customerInterests" multiple="multiple" style="width:100%;"></select>
						<?php
						$ints = WPsCRM_get_customer_field_values('interessi');
						if (empty($ints)): ?>
							<div class="alert alert-warning">
								<?php _e('Keine Interessen; Erstelle Interessen in den CRM-Einstellungen ->Seite „Kundeneinstellungen“.','cpsmartcrm') ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<div class="row form-group">
					<label class="col-sm-1 control-label" <?php echo $style_disabled?>><?php _e('Agent','cpsmartcrm')?>:</label>
					<div class="col-sm-4" <?php echo $style_disabled?>>
						<select id="selectAgent" name="selectAgent" <?php echo $agent_disabled?> style="width:54%" ></select>
					</div>
					<label class="col-sm-1 control-label"><?php _e('Kommt von','cpsmartcrm')?>:</label>
					<div class="col-sm-4">
						<select id="customerComesfrom" name="customerComesfrom" multiple="multiple" style="width:100%;"></select>
						<?php
						$provs = WPsCRM_get_customer_field_values('provenienza');
						if (empty($provs)): ?>
							<div class="alert alert-warning">
								<?php _e('Keine Quellen; erstelle Quellen in den CRM-Einstellungen ->Kundeneinstellungen Seite','cpsmartcrm') ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
        <!--END TAB 1 -->

        <!-- TAB 2: Kontakte -->
        <div>
            <div id="grid_contacts"></div>
        </div>
        <!--END TAB 2 -->
        
        <!-- TAB 3: Dokumente (Angebote & Rechnungen) -->
        <div>
            <h4 class="page-header"><?php _e('Angebote','cpsmartcrm')?></h4>
            <div id="grid_quotes"></div>
            
            <h4 class="page-header" style="margin-top:40px;"><?php _e('Rechnungen','cpsmartcrm')?></h4>
            <div id="grid_invoices"></div>
        </div>
        <!-- END TAB 3 -->
        
        <!-- TAB 4: Zeitleiste & Aktivitäten -->
        <div>
            <div style="margin-top:20px;">
                <div id="annotation">
                    <h3 style="text-align:center"><?php _e('Notizen-Zeitleiste','cpsmartcrm')?> 
                        <button type="button" class="btn btn-primary btn-sm btn-activity btn-attivita" data-id="<?php echo $ID?>" data-name="<?php echo esc_attr($cliente)?>" title="<?php _e('NEUE ANMERKUNG','cpsmartcrm')?>">
                            <i class="glyphicon glyphicon-option-horizontal"></i>
                        </button>
                    </h3>
                    <div>
                        <section id="cd-timeline" class="cd-container">
                            <?php $annotations = isset($riga["annotazioni"]) ? $riga["annotazioni"] : ""; WPsCRM_timeline_annotation($annotations)?>
                        </section>
                    </div>
                </div>
            </div>
        </div>
        <!-- END TAB 4 -->
        <?php
			do_action('WPsCRM_add_divs_to_customer_form',$email, $ID);
        ?>
    </div> <!-- schließt tabstrip -->

    <br>
    <input type="submit" style="display:none" />

    <!-- Form Actions Toolbar - AUSSEN vom tabstrip damit immer sichtbar -->
    <div class="form-actions" style="margin:20px 0;padding:15px;background:#f5f5f5;border:1px solid #ddd;border-radius:4px;">
        <button type="button" class="btn btn-success _showLoader saveForm">
            <i class="glyphicon glyphicon-floppy-disk"></i>
            <?php _e('Speichern','cpsmartcrm')?>
        </button>
        <button type="button" class="btn btn-warning resetForm">
            <i class="glyphicon glyphicon-floppy-remove"></i>
            <?php _e('Zurücksetzen','cpsmartcrm')?>
        </button>
        <?php if ($ID){?>
        <button type="button" class="btn btn-danger deleteForm">
            <i class="glyphicon glyphicon-remove"></i>
            <?php _e('Löschen','cpsmartcrm')?>
        </button>
        <span style="margin:0 10px;border-left:2px solid #ccc;height:30px;display:inline-block;"></span>
        <button type="button" class="btn btn-info btn_todo" data-id="<?php echo $ID?>" data-name="<?php echo esc_attr($cliente)?>" title="<?php _e('NEUE TODO','cpsmartcrm')?>">
            <i class="glyphicon glyphicon-tag"></i>
            <?php _e('Todo','cpsmartcrm')?>
        </button>
        <button type="button" class="btn btn-default btn-appointment btn_appuntamento" data-id="<?php echo $ID?>" data-name="<?php echo esc_attr($cliente)?>" title="<?php _e('NEUER TERMIN','cpsmartcrm')?>">
            <i class="glyphicon glyphicon-pushpin"></i>
            <?php _e('Termin','cpsmartcrm')?>
        </button>
        <button type="button" class="btn btn-primary btn-activity btn-attivita" data-id="<?php echo $ID?>" data-name="<?php echo esc_attr($cliente)?>" title="<?php _e('NEUE ANMERKUNG','cpsmartcrm')?>">
            <i class="glyphicon glyphicon-option-horizontal"></i>
            <?php _e('Anmerkung','cpsmartcrm')?>
        </button>
        <?php do_action('WPsCRM_advanced_buttons',$email);?>
        <?php }
        ?>
    </div>
</form>

<!-- Modal Dialogs für Todo, Termin und Aktivität -->
<div id="dialog_todo" style="display:none">
    <?php include(WPsCRM_DIR."/inc/crm/kunde/form_todo.php"); ?>
</div>
<div id="dialog_appuntamento" style="display:none">
    <?php include(WPsCRM_DIR."/inc/crm/kunde/form_appuntamento.php"); ?>
</div>
<div id="dialog_attivita" style="display:none;">
    <form id="new_attivita" class="form-horizontal">
        <input type="hidden" name="fk_cliente" id="attivita_fk_cliente" value="">
        <input type="hidden" name="action" value="WPsCRM_save_activity">
        
        <div class="form-group">
            <label class="col-sm-2 control-label"><?php _e('Notiz für','cpsmartcrm')?>:</label>
            <div class="col-sm-10">
                <p class="form-control-static"><strong class="name_cliente"></strong></p>
            </div>
        </div>
        
        <div class="form-group">
            <label class="col-sm-2 control-label"><?php _e('Datum','cpsmartcrm')?>:</label>
            <div class="col-sm-4">
                <input type="text" id="attivita_datum" name="datum" class="form-control" readonly>
            </div>
        </div>
        
        <div class="form-group">
            <label class="col-sm-2 control-label"><?php _e('Notiz','cpsmartcrm')?>:</label>
            <div class="col-sm-10">
                <div id="attivita_editor_toolbar" style="margin-bottom: 5px;">
                    <button type="button" class="btn btn-sm btn-default" data-command="bold" title="Fett">
                        <i class="glyphicon glyphicon-bold"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-default" data-command="italic" title="Kursiv">
                        <i class="glyphicon glyphicon-italic"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-default" data-command="insertUnorderedList" title="Liste">
                        <i class="glyphicon glyphicon-list"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-default" data-command="insertOrderedList" title="Nummerierte Liste">
                        <i class="glyphicon glyphicon-th-list"></i>
                    </button>
                </div>
                <div id="attivita_editor" contenteditable="true" style="min-height: 200px; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background: white;">
                </div>
                <textarea name="note" id="attivita_note_hidden" style="display:none;"></textarea>
            </div>
        </div>
        
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="button" class="btn btn-primary" id="btn_save_attivita">
                    <i class="glyphicon glyphicon-save"></i> <?php _e('Notiz speichern','cpsmartcrm')?>
                </button>
                <button type="button" class="btn btn-default" id="btn_cancel_attivita">
                    <i class="glyphicon glyphicon-remove"></i> <?php _e('Abbrechen','cpsmartcrm')?>
                </button>
            </div>
        </div>
    </form>
</div>

<style>
    input[type=checkbox] {
        float: initial;
    }
    
    /* Sicherstellen dass alle Modals im Dialog sichtbar sind */
    .pscrm-modal-content #dialog_todo,
    .pscrm-modal-content #dialog_appuntamento,
    .pscrm-modal-content #dialog_attivita,
    .modal-content #dialog_todo,
    .modal-content #dialog_appuntamento,
    .modal-content #dialog_attivita {
        display: block !important;
    }
    
    /* Formulare außerhalb der Modals verstecken */
    body > #dialog_todo,
    body > #dialog_appuntamento,
    body > #dialog_attivita {
        display: none !important;
    }
</style>
<?php
}
?>
