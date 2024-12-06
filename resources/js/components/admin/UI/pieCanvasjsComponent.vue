<template>
  <div
    :id="chartContainerId"
    :style="{ width: width + 'px', height: height + 'px' }"
  ></div>
</template>

<script>
import * as CanvasJS from "canvasjs";

export default {
  name: "PieCanvasjsComponent",
  props: {
    yValues: {
      type: Array,
      required: true,
      default: () => [10, 20, 30, 5, 15, 12, 8, 0],
    },
    width: {
      type: Number,
      default: 500,
    },
    height: {
      type: Number,
      default: 400,
    },
    indexLabelRadius: {
      type: Number,
      default: 50,
    },
  },
  data() {
    return {
      chartContainerId: "chartContainer-" + this._uid,
    };
  },
  computed: {
    dataPoints() {
      const defaultDataPoints = [
        { id: 8, label: "new", color: "#DDE4E4FF" },
        { id: 33, label: "renew", color: "#C3F3FF" },
        { id: 9, label: "callback", color: "#1D92F09F" },
        { id: 10, label: "deposit", color: "#21CB7BFF" },
        { id: 20, label: "pending", color: "#A3ADB7FF" },
        { id: 32, label: "potential", color: "#7FD74E" },
        { id: 7, label: "noanswer", color: "#EFA0238C" },
        { id: 12, label: "not interested", color: "#A544D2B2" },
      ];
      return this.yValues.map((value, index) => ({
        y: value,
        label: defaultDataPoints[index].label,
        color: defaultDataPoints[index].color,
      }));
    },
  },
  mounted() {
    this.renderChart();
  },
  watch: {
    dataPoints: {
      handler() {
        this.renderChart();
      },
      deep: true,
    },
  },
  methods: {
    renderChart() {
      const chart = new CanvasJS.Chart(this.chartContainerId, {
        animationEnabled: true,
        theme: "light2",
        data: [
          {
            type: "pie",
            // indexLabel: "{label}: {y}",
            indexLabel: "{label}: (#percent%)",
            indexLabelRadius: this.indexLabelRadius,
            dataPoints: this.dataPoints,
          },
        ],
      });
      chart.render();
    },
  },
};
</script>

<style scoped>
/* Add any necessary styles here */
</style>
