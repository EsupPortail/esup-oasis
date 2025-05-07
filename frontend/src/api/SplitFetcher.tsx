import { useApi } from "../context/api/ApiProvider";
import { useEffect, useState } from "react";
import { Button, Progress } from "antd";

export default function SplitFetcher(props: {
   itemsPerPage: number;
   query: any;
   setData: (data: any[]) => void;
   setIsFetching: (isFetching: boolean) => void;
   icon?: React.ReactNode;
   label?: React.ReactNode;
}) {
   const [enabled, setEnabled] = useState(false);
   const [page, setPage] = useState(1);
   const [totalItems, setTotalItems] = useState<number | null>(null);
   const [_allData, setAllData] = useState<any[]>([]);

   const { data } = useApi().useGetCollectionPaginated({
      path: "/amenagements",
      page: page,
      itemsPerPage: props.itemsPerPage, // NB_MAX_ITEMS_PER_PAGE,
      query: props.query,
      enabled: enabled,
   });

   useEffect(() => {
      setAllData((prev) => {
         if (data) {
            props.setIsFetching(true);
            setTotalItems(data?.totalItems);
            if (page * props.itemsPerPage < data?.totalItems) {
               setPage((prevPage) => prevPage + 1);
            } else {
               props.setData([...prev, ...(data?.items || [])]);
               props.setIsFetching(false);
            }
         }
         return [...prev, ...(data?.items || [])];
      });
   }, [data]);

   return enabled ? (
      <Progress
         style={{ width: 200 }}
         percent={
            totalItems ? Math.round(((page * props.itemsPerPage) / (totalItems || 1)) * 100) : 0
         }
      />
   ) : (
      <Button icon={props.icon} onClick={() => setEnabled(true)}>
         {props.label}
      </Button>
   );
}
