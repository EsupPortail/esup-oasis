import { MinusOutlined } from "@ant-design/icons";
import { Typography } from "antd";
import React from "react";

export function CopyableTextCell({
  text,
  width,
  wrap,
}: {
  text?: string | number | null;
  width?: number;
  wrap?: boolean;
}) {
  if (!text) return <MinusOutlined />;
  const content = (
    <Typography.Text
      copyable={{ text: String(text) }}
      style={{ textWrap: wrap ? "wrap" : "nowrap" }}
    >
      {text}
    </Typography.Text>
  );
  return width ? <div style={{ width }}>{content}</div> : content;
}
