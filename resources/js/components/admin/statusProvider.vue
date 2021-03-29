<template>
<v-row justify="center">
  <v-dialog v-model="dialog" max-width="600">
    <template v-slot:activator="{ on, attrs }">
      <v-btn
        color="outlined"

        v-bind="attrs"
        v-on="on"
        >
        Отчёт
      </v-btn>
    </template>
    <v-card>
      <v-card-title>
        <span class="headline">Поставщик: {{ provider.name }}</span>
        <v-spacer></v-spacer>

        <v-btn color="primary darken-1" text @click="dialog = false">
          Закрыть
        </v-btn>
      </v-card-title>
      <v-card-subtitle> Лидов в системе: {{ hm }} </v-card-subtitle>

      <v-col>
        <v-simple-table>
          <template v-slot:default>
            <thead>
              <tr>
                <th class="text-left">Статус</th>
                <th class="text-left">Показатель</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in statuses" :key="item.name">
                <td>{{ item.name }}</td>
                <td>{{ item.hm }}</td>
              </tr>
            </tbody>
          </template>
        </v-simple-table>
      </v-col>
    </v-card>
  </v-dialog>
</v-row>
</template>

<script>
import axios from "axios";
export default {
  props: ["provider"],
  data() {
    return {
      dialog: false,
      hm: 0,
      statuses: [],
    };
  },
  methods: {
    getStatuses() {
      let self = this;

      axios
        .post("/api/status_provider", { provider_id: self.provider.id })
        .then((res) => {
          self.statuses = res.data.statuses;
          self.hm = res.data.hm[0].hm;
        })
        .catch((error) => console.log(error));
    },
  },
  mounted: function () {
    this.getStatuses();
  },
  beforeUpdate: function () {
    this.getStatuses();
  },
};
</script>
