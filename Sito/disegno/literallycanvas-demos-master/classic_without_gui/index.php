<!doctype html>
<html>
  <head>
    <title>Literally Canvas</title>
    <link href="../_assets/literallycanvas.css" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no" />

    <style type="text/css">
      body {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        margin: 0;
        background-color: gray;
        height: 2000px;
      }

      .fs-container {
       width: 1000px;
       margin: 50px;
      }

      .literally {
        width: 100%;
        height: 100%;
      }

      .literally img.background, .literally > canvas {
        position: absolute;
      }

      .toolset {
        margin: 1rem;
      }

      .tool {
        background: hsla(199, 26%, 44%, 0.5);
        padding: 0.25rem;
        margin: 0.25rem;
        border-radius: 0.25rem;
        color: #000;
        text-align: center;
        text-decoration: none;
        position: relative; // give after context later
      }

      .tool.current {
        color: #fff;
        background: hsla(199, 26%, 44%, 1);
      }

      .tool:hover {
        text-decoration: underline;
        background: hsla(199, 26%, 44%, 0.75);
      }

      .toolLabel {
        font-size: 1.25rem;
      }

      #tools-sizes.disabled {
        pointer-events: none;
      }

      #tools-sizes.disabled .tool:after {
        content: ' ';
        background: hsla(0, 100%, 100%, 0.75);
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        border-radius: 0.25rem;
      }
    </style>
  </head>

  <body>
    <div class="fs-container" id="fs-container">
      <div class="literally"></div>

      <div class="toolset">
        <span class='toolLabel'>Azioni:</span>
        <a href="javascript:void(0);" class='tool' id="clear-lc">Pulisci</a>
        <a href="javascript:void(0);" class='tool' id="vectorize-lc">Vettorizza</a>
      </div>

      <div class="toolset">
        <span class='toolLabel'>Strumenti:</span>
        <a href="javascript:void(0);" class='tool' id="tool-line">Linea</a>
        <a href="javascript:void(0);" class='tool' id="tool-rectangle">Rettangolo</a>
        <a href="javascript:void(0);" class='tool' id="tool-polygon">Poligono</a>
        <a href="javascript:void(0);" class='tool' id="tool-select">Seleziona</a>
      </div>

      <br>
      <div class="svg-container"
          style="display: inline-block; border: 1px solid yellow"></div>
    </div>

    <script src="../_js_libs/jquery-1.8.2.js"></script>
    <script src="../_js_libs/literallycanvas-core.js"></script>

    <script type="text/javascript">
      var lc = null;
      var tools;
      var strokeWidths;
      var colors;

      var setCurrentByName;
      var findByName;

      // the only LC-specific thing we have to do
      var containerOne = document.getElementsByClassName('literally')[0];

      var showLC = function() {
        lc = LC.init(containerOne, {
          snapshot: JSON.parse(localStorage.getItem('drawing')),
          defaultStrokeWidth: 10,
          strokeWidths: [10, 20, 50],
          secondaryColor: 'transparent'
        });
        window.demoLC = lc;

        var save = function() {
          localStorage.setItem('line', JSON.stringify(lc.getSnapshot()));
        }

        lc.on('drawingChange', save);
        lc.on('pan', save);
        lc.on('zoom', save);

        $("#open-image").click(function() {
          window.open(lc.getImage({
            scale: 1, margin: {top: 10, right: 10, bottom: 10, left: 10}
          }).toDataURL());
        });

        $("#change-size").click(function() {
          lc.setImageSize(null, 200);
        });

        $("#reset-size").click(function() {
          lc.setImageSize(null, null);
        });

        $("#clear-lc").click(function() {
          lc.clear();
        });
        $("#vectorize-lc").click(function() {
          var canvas = document.getElementById('fs-container').childNodes;
          //var dataURL = canvas[0].child[0].toDataURL();
          console.log(canvas[1].childNodes[0].childNodes[0]);
          var dataURL = canvas[1].childNodes[0].childNodes[0].toDataURL();
          $.ajax({
            type: "POST",
            url: "save.php",
            data: { 
             imgBase64: dataURL
            }
          }).done(function(o) {
          console.log('saved'); 
          });
        });
        // Set up our own tools...
        tools = [
          {
            name: 'text',
            el: document.getElementById('tool-text'),
            tool: new LC.tools.Text(lc)
          },{
            name: 'line',
            el: document.getElementById('tool-line'),
            tool: new LC.tools.Line(lc)
          },{
            name: 'arrow',
            el: document.getElementById('tool-arrow'),
            tool: function() {
              arrow = new LC.tools.Line(lc);
              arrow.hasEndArrow = true;
              return arrow;
            }()
          },{
            name: 'dashed',
            el: document.getElementById('tool-dashed'),
            tool: function() {
              dashed = new LC.tools.Line(lc);
              dashed.isDashed = true;
              return dashed;
            }()
          },{
            name: 'ellipse',
            el: document.getElementById('tool-ellipse'),
            tool: new LC.tools.Ellipse(lc)
          },{
            name: 'tool-rectangle',
            el: document.getElementById('tool-rectangle'),
            tool: new LC.tools.Rectangle(lc)
          },{
            name: 'tool-polygon',
            el: document.getElementById('tool-polygon'),
            tool: new LC.tools.Polygon(lc)
          },{
            name: 'tool-select',
            el: document.getElementById('tool-select'),
            tool: new LC.tools.SelectShape(lc)
          }
        ];

        strokeWidths = [
          {
            name: 10,
            el: document.getElementById('sizeTool-1'),
            size: 10
          },{
            name: 20,
            el: document.getElementById('sizeTool-2'),
            size: 20
          },{
            name: 50,
            el: document.getElementById('sizeTool-3'),
            size: 50
          }
        ];

        colors = [
          {
            name: 'black',
            el: document.getElementById('colorTool-black'),
            color: '#000000'
          },{
            name: 'blue',
            el: document.getElementById('colorTool-blue'),
            color: '#0000ff'
          },{
            name: 'red',
            el: document.getElementById('colorTool-red'),
            color: '#ff0000'
          }
        ];

        setCurrentByName = function(ary, val) {
          ary.forEach(function(i) {
            $(i.el).toggleClass('current', (i.name == val));
          });
        };

        findByName = function(ary, val) {
          var vals;
          vals = ary.filter(function(v){
            return v.name == val;
          });
          if ( vals.length == 0 )
            return null;
          else
            return vals[0];
        };

        // Wire tools
        tools.forEach(function(t) {
          $(t.el).click(function() {
            var sw;

            lc.setTool(t.tool);
            setCurrentByName(tools, t.name);
            setCurrentByName(strokeWidths, t.tool.strokeWidth);
            $('#tools-sizes').toggleClass('disabled', (t.name == 'text'));
          });
        });
        setCurrentByName(tools, tools[0].name);

        // Wire Stroke Widths
        // NOTE: This will not work until the stroke width PR is merged...
        strokeWidths.forEach(function(sw) {
          $(sw.el).click(function() {
            lc.trigger('setStrokeWidth', sw.size);
            setCurrentByName(strokeWidths, sw.name);
          })
        })
        setCurrentByName(strokeWidths, strokeWidths[0].name);

        // Wire Colors
        colors.forEach(function(clr) {
          $(clr.el).click(function() {
            lc.setColor('primary', clr.color)
            setCurrentByName(colors, clr.name);
          })
        })
        setCurrentByName(colors, colors[0].name);

      };

      $(document).ready(function() {
        // disable scrolling on touch devices so we can actually draw
        $(document).bind('touchmove', function(e) {
          if (e.target === document.documentElement) {
            return e.preventDefault();
          }
        });
        showLC();
      });

      $('#hide-lc').click(function() {
        if (lc) {
          lc.teardown();
          lc = null;
        }
      });

      $('#show-lc').click(function() {
        if (!lc) { showLC(); }
      });
    </script>
  </body>
</html>
