/**
 * Hospital Alerts Bar — Frontend Script
 * Handles slider rotation, dismiss, and dot navigation.
 * Version: 1.0.0
 */
( function ( $ ) {
    'use strict';

    /**
     * Defaults (overridden by habSettings from wp_localize_script).
     */
    var defaults = {
        interval:  5000,
        animation: 400,
    };

    var settings = $.extend( {}, defaults, window.habSettings || {} );

    /**
     * AlertBar controller.
     *
     * @param {HTMLElement} el  The .hab-bar element.
     */
    function AlertBar( el ) {
        this.$bar    = $( el );
        this.$slides = this.$bar.find( '.hab-slide' );
        this.$dots   = this.$bar.find( '.hab-dot' );
        this.count   = this.$slides.length;
        this.current = 0;
        this.timer   = null;
        this.paused  = false;

        if ( this.count > 1 ) {
            this.startAutoplay();
            this.bindDots();
            this.bindHover();
            this.bindSwipe();
        }

        this.bindClose();
        this.bindKeyboard();
    }

    AlertBar.prototype = {

        /** Go to a specific slide index */
        goTo: function ( index ) {
            var self = this;

            if ( index === self.current ) return;

            // Wrap around
            if ( index >= self.count ) index = 0;
            if ( index < 0 )          index = self.count - 1;

            self.$slides.eq( self.current ).removeClass( 'hab-slide--active' );
            self.$dots.eq( self.current )
                .removeClass( 'hab-dot--active' )
                .attr( 'aria-selected', 'false' );

            self.current = index;

            self.$slides.eq( self.current ).addClass( 'hab-slide--active' );
            self.$dots.eq( self.current )
                .addClass( 'hab-dot--active' )
                .attr( 'aria-selected', 'true' );

            // Update background on dot bar to match current slide
            self.syncDotsBackground();
        },

        /** Next slide */
        next: function () {
            this.goTo( this.current + 1 );
        },

        /** Sync the dot strip colour to the active slide's background */
        syncDotsBackground: function () {
            var $active  = this.$slides.eq( this.current );
            var bg       = $active[0].style.getPropertyValue( '--slide-bg' ) || '#d32f2f';
            var textCol  = $active[0].style.getPropertyValue( '--slide-color' ) || '#fff';
            this.$bar.find( '.hab-dots' ).css({ 'background-color': bg, 'color': textCol });
        },

        /** Start autoplay timer */
        startAutoplay: function () {
            var self = this;
            self.timer = setInterval( function () {
                if ( ! self.paused ) self.next();
            }, settings.interval );
        },

        /** Bind dot buttons */
        bindDots: function () {
            var self = this;
            self.$dots.on( 'click', function () {
                self.goTo( parseInt( $( this ).data( 'index' ), 10 ) );
                self.resetTimer();
            } );
        },

        /** Pause on hover */
        bindHover: function () {
            var self = this;
            self.$bar.on( 'mouseenter focusin', function () {
                self.paused = true;
            } );
            self.$bar.on( 'mouseleave focusout', function () {
                self.paused = false;
            } );
        },

        /** Touch swipe support */
        bindSwipe: function () {
            var self      = this;
            var startX    = null;
            var threshold = 50;

            self.$bar[0].addEventListener( 'touchstart', function ( e ) {
                startX = e.touches[0].clientX;
            }, { passive: true } );

            self.$bar[0].addEventListener( 'touchend', function ( e ) {
                if ( startX === null ) return;
                var diff = startX - e.changedTouches[0].clientX;
                if ( Math.abs( diff ) >= threshold ) {
                    diff > 0 ? self.next() : self.goTo( self.current - 1 );
                    self.resetTimer();
                }
                startX = null;
            }, { passive: true } );
        },

        /** Close / dismiss button */
        bindClose: function () {
            var self = this;
            self.$bar.find( '.hab-close' ).on( 'click', function () {
                self.$bar.addClass( 'is-dismissed' );
                clearInterval( self.timer );
                // Store dismissal in sessionStorage so it stays closed during the session
                try {
                    sessionStorage.setItem( 'hab_dismissed_' + self.$bar.attr( 'id' ), '1' );
                } catch ( e ) { /* private browsing may block this */ }
            } );
        },

        /** Keyboard arrow navigation when bar has focus */
        bindKeyboard: function () {
            var self = this;
            self.$bar.on( 'keydown', function ( e ) {
                if ( self.count <= 1 ) return;
                if ( e.key === 'ArrowRight' || e.key === 'ArrowDown' ) {
                    self.next();
                    self.resetTimer();
                } else if ( e.key === 'ArrowLeft' || e.key === 'ArrowUp' ) {
                    self.goTo( self.current - 1 );
                    self.resetTimer();
                }
            } );
        },

        /** Reset the autoplay interval */
        resetTimer: function () {
            clearInterval( this.timer );
            this.startAutoplay();
        },
    };

    /* ------------------------------------------------------------------
     * Initialise all bars on DOMReady
     * ------------------------------------------------------------------ */
    $( function () {
        $( '.hab-bar' ).each( function () {
            var id = $( this ).attr( 'id' );

            // Respect session-storage dismissal
            try {
                if ( sessionStorage.getItem( 'hab_dismissed_' + id ) === '1' ) {
                    $( this ).addClass( 'is-dismissed' );
                    return; // skip init for dismissed bars
                }
            } catch ( e ) { /* ignore */ }

            new AlertBar( this );
        } );
    } );

} )( jQuery );
