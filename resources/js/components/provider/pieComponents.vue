<template>
  <Pie
    :chart-options="chartOptions"
    :chart-data="chartData"
    :chart-id="chartId"
    :dataset-id-key="datasetIdKey"
    :plugins="plugins"
    :css-classes="cssClasses"
    :styles="styles"
    :width="width"
    :height="height"
  />
</template>

<script>
import { Pie } from "vue-chartjs/legacy";
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  CategoryScale,
} from "chart.js";
import ChartDataLabels from "chartjs-plugin-datalabels";

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  ArcElement,
  CategoryScale,
  ChartDataLabels
);

export default {
  name: "PieChart",
  components: {
    Pie,
  },
  props: {
    datap: {
      type: Object,
    },
    chartId: {
      type: String,
      default: "pie-chart",
    },
    datasetIdKey: {
      type: String,
      default: "label",
    },
    width: {
      type: Number,
      default: 500,
    },
    height: {
      type: Number,
      default: 500,
    },
    cssClasses: {
      default: "",
      type: String,
    },
    styles: {
      type: Object,
      default: () => {},
    },
    plugins: {
      type: Array,
      default: () => [ChartDataLabels],
    },
  },
  data() {
    return {
      chartData: this.datap,
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
          padding: {
            top: 40,
            bottom: 40,
            left: 40,
            right: 40,
          },
        },
        plugins: {
          legend: { display: false },
          datalabels: {
            formatter: (value, context) => {
              let sum = context.dataset.data.reduce((a, b) => a + b, 0);
              let percentage = ((value / sum) * 100).toFixed(2) + "%";
              return percentage;
            },
            color: "#000",
            font: {
              weight: "bold",
              size: 16,
            },
            align: "end",
            anchor: "end",
            offset: 15,
            borderWidth: 2, // Толщина линии
            borderColor: (context) => {
              // Цвет линии можно динамически задавать
              return context.dataset.backgroundColor[context.dataIndex];
            },
            backgroundColor: "#fff", // Белый фон для читабельности
            borderRadius: 4, // Закругленные углы фона метки
            padding: {
              top: 6,
              bottom: 6,
              left: 18,
              right: 18,
            },
          },
        },
      },
    };
  },
};
</script>
