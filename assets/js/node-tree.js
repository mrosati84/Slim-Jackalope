(function () {
    angular.module('JRAdmin').controller('NodeCtrl', ['$scope', '$http', function ($scope, $http) {
        $scope.PROPERTY_TYPE = {
            1:  'String',
            2:  'Binary',
            3:  'Long',
            4:  'Double',
            5:  'Date',
            6:  'Bool',
            7:  'Name',
            8:  'Path',
            9:  'Reference',
            10: 'Weak reference',
            11: 'URI',
            12: 'Decimal'
        };

        $scope.addedProperties = [];

        $scope.addProperty = function () {
            $scope.addedProperties.push({});
            $scope.showSave = true;
        };

        $scope.save = function () {
            for (var i = 0; i < $scope.addedProperties.length; i++) {
                if (!$scope.addedProperties[i].name
                    || !$scope.addedProperties[i].type
                    || !$scope.addedProperties[i].value) {
                    return;
                }
            }

            $http.put('/node', { properties: $scope.properties.concat($scope.addedProperties) }, {
                params: {
                    id: $scope.nodeId
                }
            }).then(function(response) {
                // Success, node has been updated.
                $scope.showSave = false;
                $scope.properties = response.data.properties;
                $scope.addedProperties = [];
            }, function(response) {
                // Error updating node.
                console.error(response);
            });
        };

        $scope.removeProperty = function (propertyName) {
            if (confirm('Sure?')) {
                for (var i = 0; i < $scope.properties.length; i++) {
                    if ($scope.properties[i].name === propertyName) {
                        $scope.properties[i].value = null;
                        $scope.showSave = true;
                    }
                }
            }
        }
    }]);

    var node_list = $('#node-list'),
        $scope = undefined;

    node_list.jstree({
        core: {
            data: {
                url: '/nodes_ajax',
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

    angular.element('#node-detail').ready(function () {
        $scope = angular.element('#node-detail').scope();
    });

    node_list.on('select_node.jstree', function (ev, data) {
        $.getJSON('/node', {
            'id': data.node.id
        }, function (data) {
            $scope.$apply(function () {
                $scope.nodeId = data.id;
                $scope.title = data.name;
                $scope.properties = data.properties;
                $scope.addedProperties = [];
            });
        });
    });

    node_list.on('delete_node.jstree', function (ev, data) {
        $.ajax({
            url: '/node?' + $.param({id: data.node.id}),
            method: 'DELETE',
            contentType: 'application/json'
        });
    });

    node_list.on('create_node.jstree', function (ev, data) {
        var newName = 'New node';
        var newId = data.parent + '/' + newName;

        $(this).jstree(true).set_id(data.node, newId);

        $.ajax({
            url: '/node',
            method: 'POST',
            contentType: 'application/json',
            data: {
                parent: data.parent,
                name: newName
            }
        });
    });

    node_list.on('rename_node.jstree', function (ev, data) {
        console.log(data);
    });
}());
