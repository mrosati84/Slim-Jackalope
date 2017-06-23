(function () {
    var node_list = $('#node-list'),
        node_detail = $('#node-detail');

    node_list.jstree({
        core: {
            data: {
                url: '/nodes_json',
                data: function(node) {
                    return {
                        id: node.id
                    };
                }
            },
            "check_callback" : true
        },
        checkbox: {
            "keep_selected_style" : false,
            "whole_node": false
        },
        dnd: {
            is_draggable: function () {
                return true;
            }
        },
        plugins: [
            'checkbox',
            'contextmenu',
            'dnd'
        ]
    });

    node_list.on('select_node.jstree', function (ev, data) {
        $.getJSON('/node', {
            'id': data.node.id
        }, function (data) {
            node_detail.empty();

            var attributes = $('<table>').addClass('table')
                .attr('id', 'node-attributes')
                .append($('<thead>').append(
                    $('<tr>').append(
                        $('<th>').html('Property name'),
                        $('<th>').html('Property value')
                    )
                ))
                .append($('<tbody>'));

            node_detail.append($('<h1>').html(data.id));

            for (var i = 0; i < data.properties.length; i++) {
                var attribute = $('<tr>').append(
                    $('<td>').html(data.properties[i].name),
                    $('<td>').html(data.properties[i].value)
                );

                attributes.append(attribute);
            }

            node_detail.append(attributes);
            console.log(data);
        });
    });
}());