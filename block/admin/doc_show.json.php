{
    "_": "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>",
    "blocks": {
        "header_item": {
            "block": "core/out/header",
            "x": 261,
            "y": 0,
            "in_con": {
                "machine_type": [
                    "describe",
                    "type"
                ]
            },
            "in_val": {
                "level": 2,
                "text": "Smalldb machine: {machine_type}",
                "slot_weight": 30
            }
        },
        "show_diagram": {
            "block": "smalldb/show_diagram",
            "x": 261,
            "y": 207,
            "in_con": {
                "machine_type": [
                    "describe",
                    "type"
                ]
            },
            "in_val": {
                "slot_weight": 40
            }
        },
        "describe": {
            "block": "smalldb/describe_machine",
            "x": 0,
            "y": 114,
            "in_con": {
                "type": [
                    "admin",
                    "machine_type"
                ]
            }
        },
        "show_properties": {
            "block": "smalldb/show_properties",
            "x": 262,
            "y": 352,
            "in_con": {
                "desc": [
                    "describe",
                    "desc"
                ]
            },
            "in_val": {
                "slot_weight": 70
            }
        },
        "json_dump": {
            "block": "core/out/print_r",
            "x": 262,
            "y": 477,
            "in_con": {
                "data": [
                    "describe",
                    "raw_def"
                ]
            },
            "in_val": {
                "slot_weight": 80,
                "pretty": "json"
            }
        }
    },
    "outputs": {
        "title": [
            "describe:type"
        ],
        "done": [
            "describe:done"
        ]
    }
}