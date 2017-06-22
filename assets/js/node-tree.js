(function () {
    $(function () {
        $('#node-list').on('click', 'a', function (ev) {
            var list_element = $(this).parent();
            var icon = $(this).find('i');

            if (!icon.hasClass('glyphicon-file')) {
                icon
                    .toggleClass('glyphicon-folder-close')
                    .toggleClass('glyphicon-folder-open');
            }

            if ($(this).hasClass('expanded') || !$(this).hasClass('has-nodes')) {
                $(this).next().toggle();
                ev.preventDefault();

                return;
            }

            $(this).addClass('expanded');

            $.getJSON('/nodes_json', {
                'node_name': $(this).data('node-path')
            }, function (data) {
                var new_list = $('<ul>');

                for (var i = 0; i < data.data.children.length; i++) {
                    var has_nodes = data.data.children[i].has_nodes;
                    var arrow = has_nodes ? '<i class="glyphicon glyphicon-folder-close"></i> ' : '<i class="glyphicon glyphicon-file"></i>';

                    new_list
                        .append(
                            $('<li>').append(
                                $('<a>')
                                    .attr('href', '#')
                                    .attr('data-node-path', data.data.children[i].path)
                                    .addClass(has_nodes ? 'has-nodes' : '')
                                    .html(arrow + data.data.children[i].name)
                            )
                        );
                }

                list_element.append(new_list);
            });

            ev.preventDefault();
        });
    });
}());