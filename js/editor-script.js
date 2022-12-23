var el = wp.element.createElement;
    var __ = wp.i18n.__;
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
    var buttonControl = wp.components.Button;

    function cpopGutenButton({}) {
        return el(
            PluginPostStatusInfo,
            {
                className: 'clone-page-post-status-info'
            },
            el(
                buttonControl,
                {
                    isTertiary: true,
                    name: 'clone_page_post_link_guten',
                    isLink: true,
                    title: dt_params.cpop_post_title,
                    href : dt_params.cpop_duplicate_link+"&post="+dt_params.cpop_post_id+"&nonce="+cpop_params.dtnonce
                }, dt_params.cpop_post_text
            )
        );
    }

    registerPlugin( 'clone-page-post-status-info-plugin', {
        render: cpopGutenButton
    } );