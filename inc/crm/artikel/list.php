<?php
if ( ! defined( 'ABSPATH' ) ) exit; ?>
<script type="text/javascript">
jQuery(document).ready(function ($) {
    function loadProducts() {
        var descrizione = $("#descrizione").val();
        $.ajax({
            url: ajaxurl,
            data: {
                'action': 'WPsCRM_get_products',
                'descrizione': descrizione
            },
            dataType: 'json',
            success: function (result) {
                var table = $('#grid').DataTable();
                table.clear();
                if(result.products && Array.isArray(result.products)) {
                    table.rows.add(result.products);
                }
                table.draw();
            },
            error: function (errorThrown) {
                console.log(errorThrown);
            }
        });
    }

    // DataTable initialisieren
    $('#grid').DataTable({
        columns: [
            { data: "ID", title: "ID" },
            { data: "codice", title: "<?php _e('Code','cpsmartcrm') ?>" },
            { data: "descrizione", title: "<?php _e('Beschreibung','cpsmartcrm') ?>" },
            { data: "listino1", title: "<?php _e('Preis','cpsmartcrm') ?>" },
            {
                data: null,
                title: "<?php _e('Aktion','cpsmartcrm') ?>",
                orderable: false,
                render: function (data, type, row) {
                    return '<button class="btn-edit btn btn-xs btn-primary" data-id="' + row.ID + '"><?php _e('Bearbeiten','cpsmartcrm') ?></button>';
                }
            }
        ],
        paging: true,
        pageLength: 20,
        lengthChange: true,
        searching: false,
        ordering: true,
        info: true,
        autoWidth: false,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/de-DE.json"
        }
    });

    // Suche-Button
    $("#btn_cerca").on('click', function () {
        loadProducts();
    });

    // Direkt laden beim Start
    $("#btn_cerca").trigger("click");

    // Bearbeiten-Button
    $('#grid').on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        location.href = "<?php echo home_url() ?>/wp-admin/post.php?post=" + id + "&action=edit";
    });
});
</script>
<ul class="select-action">
    <li onClick="location.href='<?php echo home_url() ?>/wp-admin/post-new.php?post_type=services';return false;" class="bg-success" style="color:#000">
        <i class="glyphicon glyphicon-plus"></i><b> <?php _e('Neuer Gegenstand','cpsmartcrm') ?></b>
    </li>
</ul>
<!-- Optional: Suchfeld für Beschreibung -->
<!-- <input type="text" id="descrizione" placeholder="<?php _e('Beschreibung suchen','cpsmartcrm') ?>" /> -->
<input type="button" id="btn_cerca" value="" style="display:none">
<div id="grid"></div>