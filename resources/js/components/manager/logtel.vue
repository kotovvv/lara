<template>
    <v-simple-table dense v-if="logs.length" class="mb-3">
    <template v-slot:default>
      <!-- <thead>
        <tr>
          <th class="text-left">Дата</th>
          <th class="text-left">Менеджер</th>
          <th class="text-left">Статус</th>
          <th class="text-left">Сообщение</th>
        </tr>
      </thead> -->
      <tbody>
        <tr
          v-for="(item,ix) in logs"
          :key="ix"
        >
          <td>{{ item.created_at }}</td>
          <td>{{ item.fio }}</td>
          <td><div class="d-flex"><i
                :style="{
                  width:'20px',height:'20px',
                  background: item.color,
                  outline: '1px solid grey',
                }"
                class="sel_stat mr-4"
              ></i
              >{{ item.name }}</div></td>
          <td>{{ item.text }}</td>
        </tr>
      </tbody>
    </template>
  </v-simple-table>
</template>

<script>
import axios from "axios";
export default {
  props: ['lid_id'],
 data: () => ({
   logs:[],

 }),
 watch: {
  lid_id: {
    immediate: true,
    handler (val, oldVal) {
      this.tellog(this.$props.lid_id)
    }
  }
},
mounted: function () {
this.tellog(this.$props.lid_id)
},
 methods: {
   tellog(lid_id){
const self = this;
      axios
        .post("api/log/tellog", {lid_id:lid_id})
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
