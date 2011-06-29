/**
 * YUI module for online judge assignment details overlay
 *
 * @author  David Mudrak <david@moodle.com> and Sun Zhigang <sunner@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
YUI.add('moodle-assignment_onlinejudge-details', function(Y) {

    var DETAILS = function() {
        DETAILS.superclass.constructor.apply(this, arguments);
    }

    Y.extend(DETAILS, Y.Base, {
        initializer : function(config) {
            Y.all('.detailslink a').on('click', this.display, this);
        },

        display : function(e, args) {
            e.preventDefault();
            var a = e.currentTarget;
            var ajaxurl = a.get('href') + '&ajax=1';
            var closebtn = Y.Node.create('<a id="closedetailsbox" href="#"><img src="' + M.util.image_url('t/delete', 'moodle') + '" /></a>');
            Y.use('overlay', 'io', function(Y) {
                var overlay = new Y.Overlay({
                    headerContent: closebtn,
                    bodyContent: Y.Node.create('<img src="' + M.util.image_url('i/loading', 'core') + '" class="spinner" />'),
                    id: 'detailsbox',
                    constrain: true,
                    visible: true,
                    centered: true,
                    width: '60%'
                });
                overlay.render(Y.one('body'));
                closebtn.on('click', function (e) {
                        e.preventDefault();
                        a.focus();
                        this.destroy();
                    }, overlay);
                closebtn.focus();

                var ajaxcfg = {
                    method: 'get',
                    context : this,
                    on: {
                        success: function(id, o, node) {
                            overlay.set('bodyContent', o.responseText);
                        },
                        failure: function(id, o, node) {
                            var debuginfo = o.statusText;
                            if (M.cfg.developerdebug) {
                                debuginfo += ' (' + ajaxurl + ')';
                            }
                            overlay.set('bodyContent', debuginfo);
                        }
                    }
                };

                Y.io(ajaxurl, ajaxcfg);
            });
        }

    }, {
        NAME : 'onlinejudge_assignment_details',
        ATTRS : {
                 aparam : {}
        }
    });

    M.assignment_onlinejudge = M.assignment_onlinejudge || {};

    M.assignment_onlinejudge.init_details = function(config) {
        M.assignment_onlinejudge.DETAILS = new DETAILS(config);
    }

}, '@VERSION@', { requires:['overlay'] });
