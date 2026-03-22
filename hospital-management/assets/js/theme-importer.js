var tm_plugin_array = [
  {
    name: 'Magnify – Suggestive Search',
    slug: 'magnify-suggestive-search',
    source: 'https://downloads.wordpress.org/plugin/magnify-suggestive-search.zip',
    required: true,
    file_name: 'magnify-suggestive-search.php'
  }
];

jQuery(document).ready(function ($) {
  $('#import-theme-mods').on('click', async function (e) {
    e.preventDefault();

    $('.demo-importer-loader').show();
    $('#import-response').text('Checking and installing required plugins...');

    // Sequentially install and activate all required plugins
    for (let i = 0; i < tm_plugin_array.length; i++) {
      const plugin = tm_plugin_array[i];

      // Step 1: Check if plugin exists
      const checkResponse = await $.ajax({
        url: demoImporter.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'tm-check-plugin-exists',
          plugin_text_domain: plugin.slug,
          main_plugin_file: plugin.file_name,
          _ajax_nonce: demoImporter.nonce
        }
      });

      // Step 2: Install if missing
      if (!checkResponse.data || checkResponse.data.install_status !== true) {
        $('#import-response').text(`Installing ${plugin.name}...`);
        try {
          await new Promise((resolve, reject) => {
            wp.updates.installPlugin({
              slug: plugin.slug,
              success: resolve,
              error: reject
            });
          });
        } catch (err) {
          console.log('Installation failed for:', plugin.slug, err);
        }
      }

      // Step 3: Activate
      $('#import-response').text(`Activating ${plugin.name}...`);
      await $.ajax({
        url: demoImporter.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'tm_install_and_activate_plugin',
          plugin_details: {
            plugin_text_domain: plugin.slug,
            plugin_main_file: plugin.file_name,
            plugin_url: plugin.source
          },
          main_plugin_file: plugin.file_name,
          _ajax_nonce: demoImporter.nonce
        }
      });
    }

    // ✅ Step 4: Run the actual demo import now
    $('#import-response').text('Importing demo content...');
    try {
      const demoImport = await $.ajax({
        url: demoImporter.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'import_theme_mods',
          _ajax_nonce: demoImporter.nonce
        }
      });

      if (demoImport.success) {
      $('#import-response').html(`
        <div class="demo-import-success">
          <p class="success-messgae">Demo content imported successfully 🎉</p>
          <div>
            <a href="${demoImport.data.redirect}" target="_blank" class="button button-primary view-site-btn">
            View Site
            </a>
          </div>
        </div>
      `);
    } else {
      $('#import-response').text('Demo import failed: ' + demoImport.data);
    }
    } catch (error) {
      console.log(error);
      $('#import-response').text('AJAX error during import.');
    }

    $('.demo-importer-loader').hide();
  });
});

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}