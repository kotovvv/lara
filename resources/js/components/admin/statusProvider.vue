<template>
<div>
<div><b>Поставщик:</b> {{provider.name }}</div>
<div><b>Лидов в системе:</b> {{hm }}</div>
  <v-simple-table>
    <template v-slot:default>
      <thead>
        <tr>
          <th class="text-left">
            Статус
          </th>
          <th class="text-left">
            Показатель
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="item in statuses"
          :key="item.name"
        >
          <td>{{ item.name }}</td>
          <td>{{ item.hm }}</td>
        </tr>
      </tbody>
    </template>
  </v-simple-table>
  </div>
</template>

<script>
import axios from "axios";
export default {
props:["provider"],
    data () {
      return {
        hm:0,
        statuses: [ ],
      }
    },
    methods:{
getStatuses(){
  let self = this;

      axios
        .post("/api/status_provider",{provider_id:self.provider.id})
        .then((res) => {
          self.statuses = res.data.statuses
          self.hm = res.data.hm[0].hm

        })
        .catch((error) => console.log(error));
},
    },
    mounted: function (){
this.getStatuses()      
    },
    beforeUpdate: function (){
this.getStatuses()
    }
  }
</script>