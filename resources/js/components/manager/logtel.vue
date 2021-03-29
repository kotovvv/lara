<template>
    <v-simple-table dense v-if="logs.length" class="mb-3">
    <template v-slot:default>
      <thead>
        <tr>
          <th class="text-left">Дата</th>
          <th class="text-left">Менеджер</th>
          <th class="text-left">Статус</th>
          <th class="text-left">Сообщение</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(item,ix) in logs"
          :key="ix"
           :style="{background:item.color}"
        >
          <td>{{ item.created_at }}</td>
          <td>{{ item.fio }}</td>
          <td>{{ item.name }}</td>
          <td>{{ item.text }}</td>
        </tr>
      </tbody>
    </template>
  </v-simple-table>
</template>

<script>
import axios from "axios";
export default {
  props: ['tel'],
 data: () => ({
   logs:[],

 }),
 watch: {
  tel: {

    immediate: true,
    handler (val, oldVal) {

      this.tellog(this.$props.tel)
    }
  }
},
mounted: function () {
this.tellog(this.$props.tel)
},
 methods: {
   tellog(tel){
const self = this;
      axios
        .post("api/log/tellog", {tel:tel})
        .then(function (res) {

          if(res.data.length)  self.logs = res.data
          else self.logs = []
        })
        .catch(function (error) {
          console.log(error);
        });
   }
 }
}
</script>
