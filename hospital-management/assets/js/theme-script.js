function hospital_management_openNav() {
  jQuery(".sidenav").addClass('show');
}
function hospital_management_closeNav() {
  jQuery(".sidenav").removeClass('show');
}

( function( window, document ) {
  function hospital_management_keepFocusInMenu() {
    document.addEventListener( 'keydown', function( e ) {
      const hospital_management_nav = document.querySelector( '.sidenav' );

      if ( ! hospital_management_nav || ! hospital_management_nav.classList.contains( 'show' ) ) {
        return;
      }
      const elements = [...hospital_management_nav.querySelectorAll( 'input, a, button' )],
        hospital_management_lastEl = elements[ elements.length - 1 ],
        hospital_management_firstEl = elements[0],
        hospital_management_activeEl = document.activeElement,
        tabKey = e.keyCode === 9,
        shiftKey = e.shiftKey;

      if ( ! shiftKey && tabKey && hospital_management_lastEl === hospital_management_activeEl ) {
        e.preventDefault();
        hospital_management_firstEl.focus();
      }

      if ( shiftKey && tabKey && hospital_management_firstEl === hospital_management_activeEl ) {
        e.preventDefault();
        hospital_management_lastEl.focus();
      }
    } );
  }
  hospital_management_keepFocusInMenu();
} )( window, document );

var hospital_management_btn = jQuery('#button');

jQuery(window).scroll(function() {
  if (jQuery(window).scrollTop() > 300) {
    hospital_management_btn.addClass('show');
  } else {
    hospital_management_btn.removeClass('show');
  }
});

hospital_management_btn.on('click', function(e) {
  e.preventDefault();
  jQuery('html, body').animate({scrollTop:0}, '300');
});

jQuery(document).ready(function() {
    var owl = jQuery('#top-slider .owl-carousel');
    owl.owlCarousel({
    margin: 0,
    nav:false,
    autoplay : true,
    lazyLoad: true,
    autoplayTimeout: 5000,
    loop: false,
    dots: false,
    navText : ['<i class="fa fa-lg fa-chevron-left" aria-hidden="true"></i>','<i class="fa fa-lg fa-chevron-right" aria-hidden="true"></i>'],
    responsive: {
      0: {
        items: 1
      },
      576: {
        items: 1
      },
      768: {
        items: 1
      },
      1000: {
        items: 1
      },
      1200: {
        items: 1
      }
    },
    autoplayHoverPause : false,
    mouseDrag: true
  });
})

window.addEventListener('load', (event) => {
  jQuery(".loading").delay(2000).fadeOut("slow");
  jQuery(".loading2").delay(2000).fadeOut("slow");
});

var btn = document.querySelector('.toggle');
var hospital_management_btnst = true;
btn.onclick = function() {
  if(hospital_management_btnst == true) {
    document.querySelector('.toggle span').classList.add('toggle');
    document.getElementById('slidebar').classList.add('slidebarshow');
    hospital_management_btnst = false;
  }else if(hospital_management_btnst == false) {
    document.querySelector('.toggle span').classList.remove('toggle');
    document.getElementById('slidebar').classList.remove('slidebarshow');
    hospital_management_btnst = true;
  }
}

jQuery('.header-search-wrapper .search-main').click(function(){
    jQuery('.search-form-main').toggleClass('active-search');
    jQuery('.search-form-main .search-field').focus();
});

jQuery(window).scroll(function() {
  var data_sticky = jQuery('.main-header').attr('data-sticky');

  if (data_sticky == "true") {
    if (jQuery(this).scrollTop() > 1){
      jQuery('.main-header').addClass("stick_header");
    } else {
      jQuery('.main-header').removeClass("stick_header");
    }
  }
});