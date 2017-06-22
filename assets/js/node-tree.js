(function () {
    $('#node-list').jstree({
        core: {
            data: {
                url: '/nodes_json',
                data: function(node) {
                    return {
                        id: node.id
                    };
                }
            }
        },
        plugins: [
            'checkbox',
            'contextmenu',
            'dnd'
        ]
    });
}());