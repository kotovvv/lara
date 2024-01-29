<template>
  <div>
    <v-tabs v-model="tab" background-color="primary" dark>
      <v-tab> Менеджеры </v-tab>
      <v-tab> Поставщики </v-tab>
    </v-tabs>
    <v-tabs-items v-model="tab">
      <v-tab-item>
        <reportUserPie :user="$attrs.user"></reportUserPie>
      </v-tab-item>
      <v-tab-item>
        <reportProviderPie :user="$attrs.user"></reportProviderPie>
      </v-tab-item>
    </v-tabs-items>
  </div>
</template>

<script>
import reportProviderPie from "./reportProviderPie";
import reportUserPie from "./reportUserPie";
export default {
  name: "ReportsPie",
  components: {
    reportProviderPie,
    reportUserPie,
  },
  data: () => ({
    tab: 0,
  }),
};
</script>
