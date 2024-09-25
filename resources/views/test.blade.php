<!DOCTYPE html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&family=Source+Sans+Pro:wght@400;700&family=Lora:wght@400;700&family=Nunito:wght@400;700&display=swap"
        rel="stylesheet">


    {{-- <link href="/path/to/your/tailwind.css" rel="stylesheet"> --}}
    <title>Test page</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- !!! --}}
    {{-- <script src="https://unpkg.com/gojs/release/go.js"></script> --}}

    {{-- !!!!! --}}
    <script src="https://unpkg.com/cytoscape/dist/cytoscape.min.js"></script>
    <style>
        #cy {
            width: 800px;
            height: 600px;
            border: 1px solid lightgray;
        }
    </style>
</head>

{{-- <body class="font-sans bg-light text-dark">
    <footer class="bg-dark text-light p-4">
        <p>© 2024 Вебдодаток. Всі права захищені.</p>
    </footer>
</body> --}}




<body>
    <div id="cy"></div>

    <script>
        // Функція для генерації випадкового кольору
        function getRandomColor() {
            const letters = '0123456789ABCDEF';
            let color = '#';
            for (let i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
        }

        // Функція для визначення яскравості кольору
        function getBrightness(hex) {
            const r = parseInt(hex.substr(1, 2), 16);
            const g = parseInt(hex.substr(3, 2), 16);
            const b = parseInt(hex.substr(5, 2), 16);
            return (r * 0.299 + g * 0.587 + b * 0.114);
        }

        const layout = {
            name: 'cose',
            padding: 80,
            fit: true,
        }
        // const layout = {
        //     name: 'circle',
        //     fit: true, // whether to fit the viewport to the graph
        //     padding: 30, // the padding on fit     
        // }

        // Дані для вузлів та зв'язків
        const nodes = [{
                data: {
                    id: 'users'
                },
                style: {
                    'background-color': getRandomColor()
                }
            },
            {
                data: {
                    id: 'posts'
                },
                style: {
                    'background-color': getRandomColor()
                }
            },
            {
                data: {
                    id: 'phones'
                },
                style: {
                    'background-color': getRandomColor()
                }
            },
            {
                data: {
                    id: 'roles'
                },
                style: {
                    'background-color': getRandomColor()
                }
            },
            {
                data: {
                    id: 't1'
                },
                style: {
                    'background-color': getRandomColor()
                }
            },
            {
                data: {
                    id: 't2'
                },
                style: {
                    'background-color': getRandomColor()
                }
            },
            {
                data: {
                    id: 't3'
                },
                style: {
                    'background-color': getRandomColor()
                }
            },
            {
                data: {
                    id: 't4'
                },
                style: {
                    'background-color': getRandomColor()
                }
            },
            {
                data: {
                    id: 't5'
                },
                style: {
                    'background-color': getRandomColor()
                }
            },
            {
                data: {
                    id: 't6'
                },
                style: {
                    'background-color': getRandomColor()
                }
            },
            {
                data: {
                    id: 't7'
                },
                style: {
                    'background-color': getRandomColor()
                }
            },
            {
                data: {
                    id: 't8'
                },
                style: {
                    'background-color': getRandomColor()
                }
            },
            {
                data: {
                    id: 't9'
                },
                style: {
                    'background-color': getRandomColor()
                }
            },
            {
                data: {
                    id: 't10'
                },
                style: {
                    'background-color': getRandomColor()
                }
            }
        ];

        // Додавання кольору тексту залежно від яскравості фону
        nodes.forEach(node => {
            const bgColor = node.style['background-color'];
            const brightness = getBrightness(bgColor);
            node.style.color = brightness > 128 ? 'black' : 'white';
        });

        const edges = [{
                data: {
                    source: 'users',
                    target: 'posts',
                    label: '1:N'
                }
            },
            {
                data: {
                    source: 'users',
                    target: 'phones',
                    label: '1:1'
                }
            },
            {
                data: {
                    source: 'users',
                    target: 'roles',
                    label: 'N:1'
                }
            },
            {
                data: {
                    source: 'users',
                    target: 't1',
                    label: 'N:1'
                }
            },
            {
                data: {
                    source: 'users',
                    target: 't1',
                    label: 'N:1'
                }
            },
            {
                data: {
                    source: 't2',
                    target: 't3',
                    label: '1:1'
                }
            },
            {
                data: {
                    source: 't3',
                    target: 't2',
                    label: '1:N'
                }
            },
            {
                data: {
                    source: 't3',
                    target: 't3',
                    label: 'self ref'
                }
            },

            {
                data: {
                    source: 't4',
                    target: 't5',
                    label: 'circular ref'
                }
            },
            {
                data: {
                    source: 't5',
                    target: 't6',
                    label: 'circular ref'
                }
            },
            {
                data: {
                    source: 't6',
                    target: 't7',
                    label: 'circular ref'
                }
            },
            {
                data: {
                    source: 't7',
                    target: 't8',
                    label: 'circular ref'
                }
            },
            {
                data: {
                    source: 't8',
                    target: 't9',
                    label: 'circular ref'
                }
            },
            {
                data: {
                    source: 't9',
                    target: 't10',
                    label: 'circular ref'
                }
            },
            {
                data: {
                    source: 't10',
                    target: 't4',
                    label: 'circular ref'
                }
            }
        ];

        const cy = cytoscape({
            container: document.getElementById('cy'),
            elements: {
                nodes: nodes,
                edges: edges
            },
            style: [{
                    selector: 'node',
                    style: {
                        'shape': 'rectangle',
                        'width': '100px',
                        'height': '50px',
                        'label': 'data(id)',
                        'text-valign': 'center',
                        'text-halign': 'center',
                        'font-size': '12px',
                        'background-color': 'data(background-color)',
                        // 'color': 'data(color)'
                    }
                },
                {
                    selector: 'edge',
                    // style: {
                    //     'width': 2,
                    //     'line-color': '#999',
                    //     'target-arrow-color': '#999',
                    //     'target-arrow-shape': 'triangle',
                    //     'curve-style': 'bezier',
                    //     'control-point-step-size': 60,
                    //     'label': 'data(label)',
                    //     'font-size': '12px',
                    //     'text-rotation': 'autorotate',
                    //     'text-margin-y': -10
                    // }
                    style: {
                        'width': 2,
                        'line-color': '#999',
                        'target-arrow-color': '#999',
                        'target-arrow-shape': 'triangle',
                        'curve-style': 'taxi',
                        'taxi-direction': 'rightward',
                        'label': 'data(label)',
                        'font-size': '12px',
                        'text-rotation': 'autorotate',
                        'text-margin-y': -10
                    }
                }
            ],
            // layout: {
            //     name: 'grid',
            //     rows: 2
            // }
            layout: layout
        });

        cy.on('tap', 'node', function(evt) {
            const node = evt.target;
            console.log('Tapped ' + node.id());
        });
    </script>
</body>

</html>
