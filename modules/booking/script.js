function initBookingCalendar(min, max) {
  var y = new Date().getFullYear();
  var roomSelect = $E('calendar_room_id');
  var urlParams = (roomSelect && roomSelect.value > 0) ? '?room_id=' + roomSelect.value : '';

  var calendar = new Calendar("booking-calendar", {
    minYear: Math.min(min, y),
    maxYear: Math.max(max, y),
    url: WEB_URL + "index.php/booking/model/calendar/toJSON" + urlParams,
    onclick: function(d) {
      send(
        WEB_URL + "index.php/booking/model/index/action",
        "action=detail&id=" + this.id,
        doFormSubmit
      );
    }
  });

  if ($E('calendar_room_id')) {
    $G('calendar_room_id').addEvent('change', function() {
      if (this.value > 0) {
        calendar.setParams('room_id=' + this.value);
      } else {
        calendar.setParams('');
      }
    });
  }
  if ($E('room_links')) {
    forEach($E('room_links').getElementsByTagName('a'), function() {
      callClick(this, function() {
        send(
          WEB_URL + "index.php/booking/model/rooms/action",
          'action=detail&id=' + this.id.replace('room_', ''),
          doFormSubmit,
          this
        );
      });
    });
  }
}

function initRoomCalendar(min, max, roomId) {
  var y = new Date().getFullYear();
  var calendar = new Calendar("booking-calendar", {
    minYear: Math.min(min, y),
    maxYear: Math.max(max, y),
    url: WEB_URL + "index.php/booking/model/calendar/toJSON?room_id=" + roomId,
    onclick: function(d) {
      if (this.id && this.id.indexOf('_booking') > -1) {
        send(
          WEB_URL + "index.php/booking/model/index/action",
          "action=detail&id=" + this.id,
          doFormSubmit
        );
      } else {
        window.location = WEB_URL + "index.php?module=booking-booking&room_id=" + roomId + "&begin_date=" + d;
      }
    },
    onClickDay: function(d) {
      window.location = WEB_URL + "index.php?module=booking-booking&room_id=" + roomId + "&begin_date=" + d;
    }
  });
}

function initHomeRoomCards() {
  var cards = document.getElementById('home_room_cards');
  if (cards) {
    var btns = cards.querySelectorAll('.room-detail-btn');
    for (var i = 0; i < btns.length; i++) {
      callClick(btns[i], function() {
        var id = this.id.replace('room_detail_', '');
        send(
          WEB_URL + "index.php/booking/model/rooms/action",
          'action=detail&id=' + id,
          doFormSubmit,
          this
        );
      });
    }
  }
}

function initBookingApprove() {
  $G('begin_date').addEvent("change", function() {
    if (this.value) {
      $G('end_date').min = this.value;
    }
  });
  var doApprove = function() {
    var id = floatval($E('id').value),
      value = this.id.replace('change_status', '');
    if (id > 0) {
      let q = 'action=approve&id=' + id + '&status=' + value;
      send(WEB_URL + 'index.php/booking/model/report/action', q, doFormSubmit, this)
    }
  };
  callClick('change_status1', doApprove);
  callClick('change_status2', doApprove);
}

function initBookingApproved() {
  var doChanged = function() {
    let status = $E('approved_status').value;
    $E('approved_reason').parentNode.parentNode.style.display = status == 2 ? null : 'none';
  };
  $G('approved_status').addEvent('change', doChanged);
  doChanged.call(this);
}

function initBookingSettings() {
  let doChanged = function() {
    let level = $E('booking_approve_level').value.toInt();
    forEach($E('verfied').getElementsByTagName('select'), function() {
      let ds = /booking_approve_status([0-9]+)/.exec(this.id);
      if (ds) {
        $E('booking_approve_department' + ds[1]).parentNode.parentNode.parentNode.parentNode.style.display = level > 0 && level >= ds[1].toInt() ? null : 'none';
      }
    });
  };
  $G('booking_approve_level').addEvent('change', doChanged);
  doChanged.call(this);
}

/* ══════════════════════════════════════════════════════
   Room Gallery — Multi-image viewer
   ══════════════════════════════════════════════════════ */

/**
 * Navigate gallery by direction (-1 = prev, 1 = next)
 */
function roomGalleryNav(galleryId, direction) {
  var data = window.roomGalleryData && window.roomGalleryData[galleryId];
  if (!data) return;
  var newIdx = data.current + direction;
  if (newIdx < 0) newIdx = data.images.length - 1;
  if (newIdx >= data.images.length) newIdx = 0;
  roomGalleryGoto(galleryId, newIdx);
}

/**
 * Go to specific image index in gallery
 */
function roomGalleryGoto(galleryId, index) {
  var data = window.roomGalleryData && window.roomGalleryData[galleryId];
  if (!data) return;
  data.current = index;
  // Update main image
  var img = document.getElementById('room_gallery_img_' + galleryId);
  if (img) {
    img.style.opacity = '0';
    setTimeout(function() {
      img.src = data.images[index];
      img.style.opacity = '1';
    }, 150);
  }
  // Update counter
  var num = document.getElementById('room_gallery_num_' + galleryId);
  if (num) num.textContent = (index + 1);
  // Update thumbs
  var gallery = document.getElementById('room_gallery_' + galleryId);
  if (gallery) {
    var thumbs = gallery.querySelectorAll('.room-gallery-thumb');
    for (var i = 0; i < thumbs.length; i++) {
      thumbs[i].classList.toggle('active', i === index);
    }
  }
  // Update lightbox if open
  var lbImg = document.getElementById('room_lightbox_img');
  var lbNum = document.getElementById('room_lightbox_num');
  if (lbImg && document.getElementById('room_lightbox')) {
    lbImg.style.opacity = '0';
    setTimeout(function() {
      lbImg.src = data.images[index];
      lbImg.style.opacity = '1';
    }, 150);
    if (lbNum) lbNum.textContent = (index + 1) + ' / ' + data.images.length;
  }
}

/* ══════════════════════════════════════════════════════
   Room Gallery — Lightbox (Full-size image viewer)
   ══════════════════════════════════════════════════════ */

/** Currently active gallery ID for lightbox */
window._lightboxGalleryId = null;

/**
 * Open lightbox for the given gallery
 */
function roomGalleryOpenLightbox(galleryId) {
  var data = window.roomGalleryData && window.roomGalleryData[galleryId];
  if (!data) return;
  window._lightboxGalleryId = galleryId;

  // Create lightbox if it doesn't exist
  var lb = document.getElementById('room_lightbox');
  if (!lb) {
    lb = document.createElement('div');
    lb.id = 'room_lightbox';
    lb.className = 'room-lightbox';
    lb.innerHTML =
      '<div class="room-lightbox-backdrop" onclick="roomGalleryCloseLightbox()"></div>' +
      '<div class="room-lightbox-content">' +
        '<button type="button" class="room-lightbox-close" onclick="roomGalleryCloseLightbox()" title="Close">&times;</button>' +
        '<button type="button" class="room-lightbox-prev" onclick="roomGalleryLightboxNav(-1)">&#10094;</button>' +
        '<img id="room_lightbox_img" src="" alt="Full size">' +
        '<button type="button" class="room-lightbox-next" onclick="roomGalleryLightboxNav(1)">&#10095;</button>' +
        '<div class="room-lightbox-counter" id="room_lightbox_num"></div>' +
      '</div>';
    document.body.appendChild(lb);
  }

  // Set image
  var lbImg = document.getElementById('room_lightbox_img');
  var lbNum = document.getElementById('room_lightbox_num');
  lbImg.src = data.images[data.current];
  lbImg.style.opacity = '1';
  lbNum.textContent = (data.current + 1) + ' / ' + data.images.length;

  // Show
  lb.classList.add('active');
  document.body.style.overflow = 'hidden';
}

/**
 * Close lightbox
 */
function roomGalleryCloseLightbox() {
  var lb = document.getElementById('room_lightbox');
  if (lb) {
    lb.classList.remove('active');
    document.body.style.overflow = '';
  }
  window._lightboxGalleryId = null;
}

/**
 * Navigate lightbox prev/next
 */
function roomGalleryLightboxNav(direction) {
  if (window._lightboxGalleryId) {
    roomGalleryNav(window._lightboxGalleryId, direction);
  }
}

// Keyboard navigation for lightbox
document.addEventListener('keydown', function(e) {
  if (!window._lightboxGalleryId) return;
  if (e.key === 'Escape' || e.keyCode === 27) {
    roomGalleryCloseLightbox();
  } else if (e.key === 'ArrowLeft' || e.keyCode === 37) {
    roomGalleryLightboxNav(-1);
  } else if (e.key === 'ArrowRight' || e.keyCode === 39) {
    roomGalleryLightboxNav(1);
  }
});

/**
 * Open lightbox for a single image (no gallery)
 */
function roomGalleryOpenSingle(imageUrl) {
  // Create a temporary gallery data entry
  window.roomGalleryData = window.roomGalleryData || {};
  window.roomGalleryData['_single'] = {current: 0, images: [imageUrl]};
  roomGalleryOpenLightbox('_single');
}

/* ══════════════════════════════════════════════════════
   Room Image Upload — Multi-image management
   ══════════════════════════════════════════════════════ */

/**
 * Delete a room image via AJAX
 */
function deleteRoomImage(roomId, imgKey) {
  if (confirm(trans('YOU_WANT_TO_XXX').replace('XXX', DELETE))) {
    send(
      WEB_URL + 'index.php/booking/model/write/deleteImage',
      'room_id=' + roomId + '&img_key=' + imgKey,
      doFormSubmit
    );
  }
}

/**
 * Preview selected images before upload
 */
function previewRoomImages(input, maxImages) {
  var existingCount = parseInt(document.getElementById('existing_img_count').value) || 0;
  var previewContainer = document.getElementById('room_img_new_preview');
  var slotsSpan = document.getElementById('remaining_slots');
  previewContainer.innerHTML = '';

  var files = input.files;
  var totalAllowed = maxImages - existingCount;

  if (files.length > totalAllowed) {
    alert(trans('Cannot upload more than') + ' ' + maxImages + ' ' + trans('Image'));
    // Keep only allowed number of files (can't modify FileList, but show warning)
  }

  var showCount = Math.min(files.length, totalAllowed);
  for (var i = 0; i < showCount; i++) {
    var reader = new FileReader();
    reader.onload = (function(file) {
      return function(e) {
        var div = document.createElement('div');
        div.className = 'room-img-item room-img-new';
        div.innerHTML = '<img src="' + e.target.result + '" alt="new image"><span class="room-img-new-label">NEW</span>';
        previewContainer.appendChild(div);
      };
    })(files[i]);
    reader.readAsDataURL(files[i]);
  }

  // Update remaining slots count
  var remaining = Math.max(0, totalAllowed - showCount);
  if (slotsSpan) slotsSpan.textContent = remaining;
}