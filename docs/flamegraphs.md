# Flamegraphs

To help in identifying performance bottlenecks and slow performing functions, Developer Tools includes a CPU time [flamegraph](http://www.brendangregg.com/flamegraphs.html) of the current page's execution. Open the Altis Developer Tools and navigate to the "Xray -> Flamegraph" panel.

![Example Flamegraph showing function calls and their execution times](./assets/qm-flamegraph.png)

The flamegraph shows execution time along the X axis. The vertical axis displays the call-depth of the PHP stack at that point in time. The flamegraph can tell you very quickly what is using the most time in the current page generation. Items in the flamegraph are clickable to zoom-in on the specific call. To zoom back out again click on a function below the one currently zoomed into.

Flamegraphs are generated with [our fork](https://github.com/humanmade/xhprof) of the [XHProf PHP extension](https://www.php.net/manual/en/book.xhprof.php), using sampling mode. The sampling interval is set to 5 milliseconds. This is considered the "resolution" of the performance profile, so you should not consider anything taking 5ms or less to be accurately represented in the flamegraph.
