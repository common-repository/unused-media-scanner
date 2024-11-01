jQuery(document).ready(function ($) {
  const { __, _x, _n, _nx } = wp.i18n;
  $("#media_scanner_results").hide();
  $("#media_scanner").click(function (e) {
    e.preventDefault();
    $("#media_scanner").prop("disabled", true);
    $("#delete_panel").hide();

    nonce = jQuery(this).attr("data-nonce");

    $.ajax({
      type: "post",
      dataType: "json",
      url: EMSC_media_scanner_ajax.ajaxurl,
      data: {
        action: "EMSC_media_scanner",
        media_scanner: "1",
        include_drafts: "1",
        include_revision: "1",
        media_scanner_nonce: nonce,
      },
      success: function (response) {
        console.log(response);
        var obj = response;
        var count_used = 0;
        var count_unused = 0;

        $("#media_scanner_msg").text("");
        content_unused = "";
        content_used = "";

        $.each(obj, function (i, val) {
          var refcount = 0;
          if (val.ref !== undefined) {
            refcount = val.ref.length;
          }

          // If used
          if (refcount > 0) {
            content_used +=
              '<div rel="used" class="media_item media_item_used" data-id="' +
              val.id +
              '">';
            content_used +=
              '<img class="media_item_preview" src="' +
              val.url +
              '" loading="lazy" /><p>';
            content_used +=
              __("ID", "unused-media-scanner") + ": " + val.id + "<br />";
            content_used +=
              __("URL", "unused-media-scanner") + ": " + val.url + "<br />";
            content_used +=
              __("URL Bare", "unused-media-scanner") +
              ": " +
              val.url_bare +
              "<br />";

            if (refcount > 0) {
              content_used +=
                "<br /><strong>" +
                __("References", "unused-media-scanner") +
                "</strong><br />";
              content_used += "<table>";
              content_used +=
                "<tr><th>" +
                __("ID", "unused-media-scanner") +
                "</th><th>" +
                __("Type", "unused-media-scanner") +
                "</th><th>" +
                __("Title", "unused-media-scanner") +
                "</th><th>" +
                __("Edit", "unused-media-scanner") +
                "</th></tr>";

              //ref_arr = recursiveArraySort(val.ref);
              ref_arr = val.ref;

              ref_arr.forEach((refs) => {
                content_used += "<tr class='" + refs.type + "'>";
                content_used += "<td>";
                content_used += refs.id;
                content_used += "</td>";
                content_used += "<td>";
                content_used += refs.type;
                content_used += "</td>";
                content_used += "<td>";
                content_used += refs.title;
                content_used += "</td>";
                content_used += "<td>";
                if (refs.edit_link) {
                  content_used +=
                    "<a href='" +
                    refs.edit_link +
                    "' target='_blank'>" +
                    __("Edit item", "unused-media-scanner") +
                    "</a>";
                }
                content_used += "&nbsp;</td>";
                content_used += "</tr>";
              });

              content_used += "</table>";
            }
            content_used += "</p></div>";
            count_used++;
          } // No references found - unused
          else {
            content_unused +=
              '<div rel="unused" class="media_item media_item_unused" data-id="' +
              val.id +
              '">';
            content_unused +=
              '<input type="checkbox" class="media_item_check" name="post_id" value="' +
              val.id +
              '" />';

            content_unused +=
              '<img class="media_item_preview" src="' +
              val.url +
              '" loading="lazy" /><p>';
            content_unused +=
              __("ID", "unused-media-scanner") + ": " + val.id + "<br />";
            content_unused +=
              __("URL", "unused-media-scanner") + ": " + val.url + "<br />";
            content_unused +=
              __("URL Bare", "unused-media-scanner") +
              ": " +
              val.url_bare +
              "<br />";

            content_unused += "</p></div>";
            count_unused++;
          }
        });

        $("#media_scanner").prop("disabled", false);

        $(".count_unused").text(count_unused);
        $(".count_used").text(count_used);

        $("#content_unused").html(content_unused);
        $("#content_used").html(content_used);

        $("#media_scanner_results").show();

        $(".media_item_check").change(function (e) {
          e.preventDefault();
          if ($(".media_item_check:checked").length > 0) {
            $("#delete_panel").show();
          } else {
            $("#delete_panel").hide();
          }
        });
      },
    });
  });

  $("#media_remove").click(function (e) {
    e.preventDefault();
    nonce = jQuery(this).attr("data-nonce");
    var trashIDs = $(".media_item_check:checked")
      .map(function () {
        return $(this).val();
      })
      .get();

    var permDel = $("#media_remove_check_perm").is(":checked");

    var num_deleted = 0;

    if (
      confirm(
        __("Are you sure you want to delete ", "unused-media-scanner") +
          trashIDs.length +
          __(
            " images? This is a descructive action and cannot be reversed. Please wait until the process has completed before navigating away from this page.",
            "unused-media-scanner"
          )
      )
    ) {
      $.ajax({
        type: "post",
        url: EMSC_media_scanner_ajax.ajaxurl,
        data: {
          action: "EMSC_media_delete",
          media_delete_nonce: nonce,
          media_ids: trashIDs,
          perm_delete: permDel,
        },
        success: function (response) {
          var deleted_ids = String(response);
          var deleted_ids_array = deleted_ids.split(",");
          deleted_ids_array.forEach(function (deleted_id, index) {
            $('.media_item[data-id="' + deleted_id + '"]').remove();
            num_deleted++;
          });
          alert(num_deleted + __(" images deleted", "unused-media-scanner"));
          $(".count_unused").text($(".media_item_unused").length);
          console.log(response);
        },
      });
    }
  });

  const recursiveArraySort = (list, parent = { id: undefined, level: 0 }) => {
    let result = [];

    /**
     * Get every element whose parent_id attribute matches the parent's id.
     */
    const children = list.filter((item) => item.parent_id === parent.id);
    /**
     * Set the level based on the parent level for each element identified,
     * add them to the result array, then recursively sort the children.
     */
    children.forEach((child) => {
      child.level = parent.level + 1;
      result = [...result, child, ...recursiveArraySort(list, child)];
    });

    return result;
  };
});

/** TABS  **/
var tabs;
/**
 * Get Tab Key
 */
function getTabKey(href) {
  return href.replace("#", "");
}
/**
 * Hide all tabs
 */
function hideAllTabs() {
  tabs.each(function () {
    var href = getTabKey(jQuery(this).attr("href"));
    jQuery("#" + href).hide();
  });
}
/**
 * Activate Tab
 */
function activateTab(tab) {
  if (!isNullOrUndefined(tab) && typeof tab === 'function') {
    var href = getTabKey(tab.attr("href"));
    tabs.removeClass("nav-tab-active");
    tab.addClass("nav-tab-active");
    jQuery("#" + href).show();
  }
}

function isNullOrUndefined(value) {
  return value === undefined || value === null;
}
jQuery(document).ready(function ($) {
  var activeTab, firstTab;
  // First load, activate first tab or tab with nav-tab-active class
  firstTab = false;
  activeTab = false;
  tabs = $("a.nav-js-tab");
  hideAllTabs();
  tabs.each(function () {
    var href = $(this).attr("href").replace("#", "");
    if (!firstTab) {
      firstTab = $(this);
    }
    if ($(this).hasClass("nav-tab-active")) {
      activeTab = $(this);
    }
  });
  if (!activeTab) {
    activeTab = firstTab;
  }
  activateTab(activeTab);
  //Click tab
  tabs.click(function (e) {
    e.preventDefault();
    hideAllTabs();
    activateTab($(this));
  });
});
