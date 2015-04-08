document.observe("dom:loaded", function() {
    var script_url = $('url_script').readAttribute('src');
    var body = $$('body')[0];
    var current_section = '';

    if (typeof Checkout !== 'undefined') {
        Checkout.prototype.gotoSection = function(section) {
            if(section !== current_section) {
                current_section = section;

                var content_script = $('lengow_tracker').innerHTML;
                var script = new Element('script', {type: 'text/javascript', src: script_url});

                if (section === 'billing' && this.method === 'guest') {
                    window.page = 'lead';
                    body.appendChild(script);
                } else if (section === 'payment') {
                    window.page = 'basket';
                    body.appendChild(script);
                    // Set var page to 'basket'
                    /*var new_content = content_script.replace(/page = \'\w+\'/, 'page = \'basket\'')
                    var script = new Element('script', {type: 'text/javascript', src: script_url});
                    alert(new_content);*/
                    //$('lengow_tracker').update(new_content);

                }
            }

            var sectionElement = $('opc-' + section);
            sectionElement.addClassName('allow');
            this.accordion.openSection('opc-' + section);
            this.reloadProgressBlock(section);
        }
    }
});