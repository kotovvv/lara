<template>
    <v-simple-table dense>
    <template v-slot:default>
      <thead>
        <tr>
          <th class="text-left">
            Name
          </th>
          <th class="text-left">
            Calories
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(item,ix) in logs"
          :key="ix"
        >
          <td>{{ item.name }}</td>
          <td>{{ item.calories }}</td>
        </tr>
      </tbody>
    </template>
  </v-simple-table>
</template>

<script>
import axios from "axios";
export default {
 data: () => ({
   props: ['tel'],
   logs:[],

 }),
mounted: function () {
tellog(this$props.tel)
},
 methods: {
   tellog(tel){
      axios
        .post("api/log/tellog", {tel:tel})
        .then(function (response) {
          console.log(response);
        })
        .catch(function (error) {
          console.log(error);
        });
   }
 }
}
</script>